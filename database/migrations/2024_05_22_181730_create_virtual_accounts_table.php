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
        Schema::create('virtual_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained()->onDelete('cascade');
            $table->string('account_number', 10);
            $table->string('provider', 12);
            $table->string('account_name');
            $table->string('bank_name');
            $table->string('bank_code', 5);
            $table->json('provider_data')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('activated_at')->nullable();
            $table->timestamps();
        });

        // unique columns
        Schema::table('virtual_accounts', function (Blueprint $table) {
            $table->unique(['account_number', 'bank_code']);
            $table->unique(['account_id', 'provider']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('virtual_accounts');
    }
};
