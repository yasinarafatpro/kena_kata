<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("orders", function (Blueprint $t) {
            $t->id();
            $t->foreignId("user_id")->constrained()->restrictOnDelete();
            $t->decimal("total_amount", 12, 2);
            $t->char("currency", 3);
            $t->string("status", 20)->default("pending")->index();
            $t->string("payment_method", 20);
            $t->string("transaction_id")->nullable()->unique();
            $t->json("shipping_address");
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("orders");
    }
};
