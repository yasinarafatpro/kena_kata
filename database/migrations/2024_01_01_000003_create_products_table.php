<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("products", function (Blueprint $t) {
            $t->id();
            $t->foreignId("category_id")->constrained()->restrictOnDelete();
            $t->string("name");
            $t->string("slug")->unique();
            $t->text("description")->nullable();
            $t->decimal("price", 10, 2);
            $t->unsignedInteger("stock")->default(0);
            $t->string("image_url", 500)->nullable();
            $t->boolean("is_active")->default(true);
            $t->timestamps();
            $t->index(["category_id", "is_active", "price"]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("products");
    }
};
