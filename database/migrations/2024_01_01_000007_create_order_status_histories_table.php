<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("order_status_histories", function (Blueprint $t) {
            $t->id();
            $t->foreignId("order_id")->constrained()->cascadeOnDelete();
            $t->string("status", 20);
            $t->string("note")->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("order_status_histories");
    }
};
