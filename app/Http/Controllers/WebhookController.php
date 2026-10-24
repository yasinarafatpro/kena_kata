<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Payments\PaymentGatewayFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function __invoke(Request $request, string $provider, PaymentGatewayFactory $factory)
    {
        abort_unless(in_array($provider, ['stripe', 'paypal'], true), 404);

        $result = $factory->make($provider)->handleWebhook($request);   // aborts 400 on a bad signature
        if ($result->outcome === 'ignored') {
            return response()->noContent();
        }

        DB::transaction(function () use ($provider, $result) {
            // Idempotency: a replayed event inserts 0 rows and is skipped. If anything below throws,
            // this insert rolls back too, so the provider's retry is processed again.
            $inserted = DB::table('payment_events')->insertOrIgnore([
                'provider' => $provider, 'event_id' => $result->eventId,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            if (! $inserted) {
                return;
            }

            $order = Order::whereKey($result->orderId)->lockForUpdate()->first();
            if (! $order) {
                return;
            }

            if ($result->outcome === 'paid') {
                if ($result->amountMinor !== $order->amountMinor()) {
                    Log::critical('Webhook amount mismatch', ['order' => $order->id, 'received' => $result->amountMinor]);

                    return;
                }
                if ($order->status === OrderStatus::Pending) {
                    $order->transitionTo(OrderStatus::Paid, "Payment confirmed via {$provider}");
                } else {
                    // e.g. paid after the order expired: needs a refund / manual review
                    Log::critical('Payment received for a non-pending order', ['order' => $order->id]);
                }
            } else {
                // A failed attempt is not final (the buyer can retry); the expiry job cancels abandoned orders.
                $order->statusHistories()->create(['status' => $order->status->value, 'note' => 'Payment attempt failed']);
            }
        });

        return response()->noContent();
    }
}
