<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->nullable()->constrained('accounts')->onDelete('cascade');
            $table->string('reference')->nullable();
            $table->string('type')->nullable();
            $table->string('category')->nullable();
            $table->string('narration')->nullable();
            $table->decimal('amount', 20, 2)->default(0.00);
            $table->decimal('current_balance', 20, 2)->default(0.00);
            $table->decimal('previous_balance', 20, 2)->default(0.00);
            $table->string('status')->nullable();
            $table->timestamps();

            // reference and account_id should be unique
            $table->unique(['reference', 'account_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('histories');
    }
};
