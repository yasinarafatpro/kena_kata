<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CartController extends Controller
{
    public function index(Request $request)
    {
        return $request->user()->cartItems()
            ->with('product:id,name,slug,price,stock,image_url')->get();
    }

    public function store(Request $request)
    {
        $d = $request->validate(['product_id' => 'required|integer', 'quantity' => 'required|integer|min:1|max:99']);
        $this->addLine($request->user(), $d['product_id'], $d['quantity']);

        return response()->noContent(201);
    }

    /** Folds the guest's localStorage cart into the database cart right after sign-in. */
    public function merge(Request $request)
    {
        $d = $request->validate([
            'items'              => 'required|array|max:50',
            'items.*.product_id' => 'required|integer',
            'items.*.quantity'   => 'required|integer|min:1|max:99',
        ]);

        DB::transaction(function () use ($request, $d) {
            foreach ($d['items'] as $i) {
                try {
                    $this->addLine($request->user(), $i['product_id'], $i['quantity']);
                } catch (HttpResponseException|ValidationException|\Illuminate\Database\Eloquent\ModelNotFoundException) {
                    // skip items that are no longer available
                }
            }
        });

        return response()->noContent();
    }

    public function update(Request $request, int $id)
    {
        $d = $request->validate(['quantity' => 'required|integer|min:1|max:99']);
        $item = $request->user()->cartItems()->with('product')->findOrFail($id);   // scoped: no IDOR
        $item->update(['quantity' => min($d['quantity'], $item->product->stock)]);

        return $item;
    }

    public function destroy(Request $request, int $id)
    {
        $request->user()->cartItems()->findOrFail($id)->delete();

        return response()->noContent();
    }

    private function addLine(User $user, int $productId, int $qty): void
    {
        $product = Product::active()->findOrFail($productId);
        if ($product->stock < 1) {
            throw ValidationException::withMessages(['product_id' => 'This product is out of stock.']);
        }

        $item = CartItem::firstOrNew(['user_id' => $user->id, 'product_id' => $product->id]);
        $item->quantity = min(($item->quantity ?? 0) + $qty, $product->stock);
        $item->save();
    }
}
