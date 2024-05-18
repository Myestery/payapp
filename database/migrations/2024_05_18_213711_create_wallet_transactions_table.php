<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->tinyInteger('status');
            $table->decimal('total_sent', 20, 2);
            $table->decimal('total_debit', 20, 2);
            $table->string('message');
            $table->string('currency')->default('NGN');
            $table->json('payload');
            $table->string('idempotency', 1000);
            $table->string('provider_reference', 100)->nullable();
            $table->string('provider', 100)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
