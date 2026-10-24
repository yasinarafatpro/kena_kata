<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = ['category_id', 'name', 'slug', 'description', 'price', 'stock', 'image_url', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];

    public function category() { return $this->belongsTo(Category::class); }
    public function scopeActive($q) { return $q->where('is_active', true); }
}
