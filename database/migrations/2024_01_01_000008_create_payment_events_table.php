<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("payment_events", function (Blueprint $t) {
            $t->id();
            $t->string("provider", 20);
            $t->string("event_id");
            $t->timestamps();
            $t->unique(["provider", "event_id"]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("payment_events");
    }
};
