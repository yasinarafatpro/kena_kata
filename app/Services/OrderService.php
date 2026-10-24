<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function createFromCart(User $user, array $shipping, string $method): Order
    {
        return DB::transaction(function () use ($user, $shipping, $method) {
            $cart = CartItem::where('user_id', $user->id)->orderBy('product_id')->get();
            if ($cart->isEmpty()) {
                throw ValidationException::withMessages(['cart' => 'Your cart is empty.']);
            }

            // Row locks (sorted by id to avoid deadlocks) prevent overselling under concurrency.
            $products = Product::whereIn('id', $cart->pluck('product_id'))
                ->where('is_active', true)->orderBy('id')->lockForUpdate()->get()->keyBy('id');

            $totalMinor = 0;
            $lines = [];
            foreach ($cart as $line) {
                $p = $products->get($line->product_id);
                if (! $p || $p->stock < $line->quantity) {
                    throw ValidationException::withMessages([
                        'cart' => ($p->name ?? 'An item') . ' is not available in the requested quantity.',
                    ]);
                }
                $p->decrement('stock', $line->quantity);
                // Price always comes from the database, never from the client.
                $totalMinor += (int) round($p->price * 100) * $line->quantity;
                $lines[] = [
                    'product_id'   => $p->id,
                    'product_name' => $p->name,
                    'quantity'     => $line->quantity,
                    'price'        => $p->price,
                ];
            }

            $order = Order::create([
                'user_id'          => $user->id,
                'total_amount'     => number_format($totalMinor / 100, 2, '.', ''),
                'currency'         => config('payments.currency'),
                'status'           => OrderStatus::Pending,
                'payment_method'   => $method,
                'shipping_address' => $shipping,
            ]);
            $order->items()->createMany($lines);
            $order->statusHistories()->create(['status' => 'pending', 'note' => 'Order created']);
            CartItem::where('user_id', $user->id)->delete();

            return $order;
        });
    }

    /** Cancels the order and returns its stock. For paid orders, refund through the gateway first. */
    public function cancel(Order $order, string $note): void
    {
        DB::transaction(function () use ($order, $note) {
            $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            $order->transitionTo(OrderStatus::Cancelled, $note);
            foreach ($order->items()->orderBy('product_id')->get() as $item) {
                Product::whereKey($item->product_id)->increment('stock', $item->quantity);
            }
        });
    }

    /** Releases stock held by abandoned online-payment checkouts (cash on delivery is exempt). */
    public function expireStale(int $minutes): int
    {
        $ids = Order::where('status', OrderStatus::Pending->value)
            ->where('payment_method', '!=', 'cod')
            ->where('created_at', '<', now()->subMinutes($minutes))
            ->pluck('id');

        $count = 0;
        foreach ($ids as $id) {
            try {
                $this->cancel(Order::findOrFail($id), 'Payment window expired');
                $count++;
            } catch (\DomainException) {
                // paid in the meantime
            }
        }

        return $count;
    }
}
