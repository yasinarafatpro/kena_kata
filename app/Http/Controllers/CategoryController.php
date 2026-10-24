<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index()
    {
        return Category::with('children:id,parent_id,name,slug')
            ->whereNull('parent_id')->orderBy('name')->get(['id', 'name', 'slug']);
    }

    public function store(Request $request)
    {
        $d = $request->validate([
            'name'      => 'required|string|max:120',
            'parent_id' => 'nullable|exists:categories,id',
        ]);

        $slug = $base = Str::slug($d['name']);
        for ($i = 2; Category::where('slug', $slug)->exists(); $i++) {
            $slug = "$base-$i";
        }

        return response()->json(Category::create([...$d, 'slug' => $slug]), 201);
    }
}
