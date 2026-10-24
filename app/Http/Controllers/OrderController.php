<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        return $request->user()->orders()->with('items')->latest('id')->paginate(10);
    }

    public function show(Request $request, int $id)
    {
        return $request->user()->orders()->with(['items', 'statusHistories'])->findOrFail($id);
    }

    // ---- admin ----

    public function adminIndex(Request $request)
    {
        return Order::with(['user:id,name,email', 'items'])
            ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))
            ->latest('id')->paginate(20);
    }

    public function updateStatus(Request $request, Order $order, OrderService $orders)
    {
        $d = $request->validate([
            'status' => ['required', Rule::enum(OrderStatus::class)],
            'note'   => 'nullable|string|max:255',
        ]);
        $to = OrderStatus::from($d['status']);

        try {
            if ($to === OrderStatus::Cancelled) {
                $orders->cancel($order, $d['note'] ?? 'Cancelled by admin');
            } else {
                DB::transaction(fn () => Order::whereKey($order->id)->lockForUpdate()->firstOrFail()
                    ->transitionTo($to, $d['note'] ?? null));
            }
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return $order->fresh(['user:id,name,email', 'items']);
    }
}
