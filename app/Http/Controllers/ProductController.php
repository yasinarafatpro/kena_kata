<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $r = $request->validate([
            'search'    => 'nullable|string|max:100',
            'category'  => 'nullable|string|max:150',
            'min_price' => 'nullable|numeric|min:0',
            'max_price' => 'nullable|numeric|min:0',
            'in_stock'  => 'nullable|boolean',
            'sort'      => 'nullable|in:newest,price_asc,price_desc,name',
        ]);

        $q = Product::active()->with('category:id,name,slug');

        $q->when($r['search'] ?? null, fn ($q, $s) =>
            $q->where('name', 'like', '%' . addcslashes($s, '%_\\') . '%'));

        $q->when($r['category'] ?? null, function ($q, $slug) {
            $cat = Category::where('slug', $slug)->first();
            // A parent category also matches products in its subcategories.
            $ids = $cat ? $cat->children()->pluck('id')->push($cat->id) : collect([0]);
            $q->whereIn('category_id', $ids);
        });

        $q->when($r['min_price'] ?? null, fn ($q, $v) => $q->where('price', '>=', $v))
          ->when($r['max_price'] ?? null, fn ($q, $v) => $q->where('price', '<=', $v))
          ->when($r['in_stock'] ?? false, fn ($q) => $q->where('stock', '>', 0));

        match ($r['sort'] ?? 'newest') {
            'price_asc'  => $q->orderBy('price'),
            'price_desc' => $q->orderByDesc('price'),
            'name'       => $q->orderBy('name'),
            default      => $q->latest('id'),
        };

        return $q->paginate(12)->withQueryString();
    }

    public function show(Product $product)
    {
        abort_unless($product->is_active, 404);

        return $product->load('category:id,name,slug');
    }

    // ---- admin ----

    public function adminIndex(Request $request)
    {
        return Product::with('category:id,name')
            ->when($request->query('search'), fn ($q, $s) =>
                $q->where('name', 'like', '%' . addcslashes($s, '%_\\') . '%'))
            ->latest('id')->paginate(20);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['slug'] = $this->uniqueSlug($data['name']);

        return response()->json(Product::create($data), 201);
    }

    public function update(Request $request, Product $product)
    {
        $product->update($this->validated($request));

        return $product->fresh();
    }

    public function destroy(Product $product)
    {
        // Retire instead of delete so past orders keep their product reference.
        $product->update(['is_active' => false]);

        return response()->noContent();
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string|max:10000',
            'price'       => 'required|numeric|min:0|max:99999999',
            'stock'       => 'required|integer|min:0|max:1000000',
            'image_url'   => 'nullable|url|max:500',
            'is_active'   => 'sometimes|boolean',
        ]);
    }

    private function uniqueSlug(string $name): string
    {
        $slug = $base = Str::slug($name) ?: 'product';
        for ($i = 2; Product::where('slug', $slug)->exists(); $i++) {
            $slug = "$base-$i";
        }

        return $slug;
    }
}
