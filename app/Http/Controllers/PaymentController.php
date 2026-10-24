<?php

namespace App\Http\Controllers;

use App\Payments\PaymentGatewayFactory;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PaymentController extends Controller
{
    public function methods()
    {
        return ['methods' => config('payments.enabled'), 'currency' => config('payments.currency')];
    }

    public function checkout(Request $request, OrderService $orders, PaymentGatewayFactory $factory)
    {
        $data = $request->validate([
            'payment_method'       => ['required', Rule::in(config('payments.enabled'))],
            'shipping.name'        => 'required|string|max:120',
            'shipping.phone'       => 'required|string|max:30',
            'shipping.line1'       => 'required|string|max:255',
            'shipping.line2'       => 'nullable|string|max:255',
            'shipping.city'        => 'required|string|max:100',
            'shipping.postal_code' => 'required|string|max:20',
            'shipping.country'     => 'required|string|size:2',
        ]);

        $order = $orders->createFromCart($request->user(), $data['shipping'], $data['payment_method']);

        try {
            $result = $factory->make($data['payment_method'])->createPayment($order);
        } catch (\Throwable $e) {
            report($e);
            $orders->cancel($order, 'Payment initialisation failed');   // releases the reserved stock

            return response()->json(['message' => 'The payment provider is unavailable. Please try again.'], 502);
        }

        $order->update(['transaction_id' => $result->reference]);

        return response()->json([
            'order_id' => $order->id,
            'total'    => $order->total_amount,
            'currency' => $order->currency,
            'payment'  => $result->toArray(),
        ], 201);
    }
}
