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
        Schema::create('withdrawals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->nullable()->constrained('accounts');
            $table->string('bank_name');
            $table->string('account_name');
            $table->string('account_number');
            $table->string('bank_code');
            $table->decimal('amount', 20, 2);
            $table->string('provider');
            $table->string('status');
            $table->string('reference');
            $table->string('session_id')->nullable();
            $table->boolean('wallet_debited');
            $table->boolean('value_given');
            $table->string('response_code')->nullable();
            $table->string('response_message')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('withdrawals');
    }
};
