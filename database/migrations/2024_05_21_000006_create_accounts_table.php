<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedbigInteger('user_id')->unique();
            $table->decimal('balance', 20, 2)->default(0.00);
            $table->decimal('transaction_limit', 20, 2)->default(1_000_000);
            $table->string('currency')->default('NGN');
            $table->boolean('email_subscribe')->default(true);
            // virtual account
            $table->timestamps();
            // foreign keys
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('accounts');
    }
};
