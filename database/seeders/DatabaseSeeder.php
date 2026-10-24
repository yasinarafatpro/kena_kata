<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('ADMIN_EMAIL', 'admin@example.com');
        if (! User::where('email', $email)->exists()) {
            // forceCreate: `role` is intentionally not mass-assignable.
            User::forceCreate([
                'name'     => 'Admin',
                'email'    => $email,
                'password' => Hash::make(env('ADMIN_PASSWORD', 'ChangeMe!12345')),
                'role'     => 'admin',
            ]);
        }

        $tree = [
            'Clothing'    => ['T-shirts', 'Outerwear'],
            'Home'        => ['Kitchen', 'Decor'],
            'Accessories' => [],
        ];
        $cats = [];
        foreach ($tree as $parent => $children) {
            $p = Category::firstOrCreate(['slug' => Str::slug($parent)], ['name' => $parent]);
            $cats[$parent] = $p->id;
            foreach ($children as $child) {
                $c = Category::firstOrCreate(['slug' => Str::slug($child)], ['name' => $child, 'parent_id' => $p->id]);
                $cats[$child] = $c->id;
            }
        }

        $products = [
            ['Classic cotton tee', 'T-shirts', 18.00, 40],
            ['Heavyweight pocket tee', 'T-shirts', 24.00, 25],
            ['Striped long-sleeve tee', 'T-shirts', 28.50, 0],
            ['Water-resistant shell jacket', 'Outerwear', 89.00, 12],
            ['Quilted vest', 'Outerwear', 64.00, 8],
            ['Cast iron skillet', 'Kitchen', 34.00, 15],
            ['Stoneware mug set', 'Kitchen', 22.00, 30],
            ['Linen apron', 'Kitchen', 19.00, 20],
            ['Woven wall hanging', 'Decor', 42.00, 6],
            ['Ceramic vase', 'Decor', 31.00, 14],
            ['Canvas tote bag', 'Accessories', 15.00, 50],
            ['Leather card holder', 'Accessories', 26.00, 18],
        ];
        foreach ($products as [$name, $cat, $price, $stock]) {
            Product::updateOrCreate(['slug' => Str::slug($name)], [
                'category_id' => $cats[$cat],
                'name'        => $name,
                'description' => "{$name}. Replace this demo description from the admin dashboard.",
                'price'       => $price,
                'stock'       => $stock,
                'is_active'   => true,
            ]);
        }
    }
}
