<?php

namespace Database\Seeders;

use App\Models\User;
use App\Payments\WalletConst;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // $table->unsignedbigInteger('user_id')->unique();
        // $table->decimal('balance', 20, 2)->default(0.00);
        // $table->decimal('transaction_limit', 20, 2)->default(1_000_000);
        // $table->string('currency')->default('NGN');
        // $table->boolean('email_subscribe')->default(true);
        // // virtual account
        // $table->timestamps();
        // // foreign keys
        // $table->foreign('user_id')->references('id')->on('users')->onDel

        DB::table('accounts')->insert([
            'balance' => 0.00,
            'name' => 'FLUTTERWAVE GL',
            'transaction_limit' => 1_000_000_000_000_000,
            'currency' => 'NGN',
            'account_type' => WalletConst::GL,
            'email_subscribe' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('accounts')->insert([
            'balance' => 0.00,
            'name' => 'PAYSTACK GL',
            'transaction_limit' => 1_000_000_000_000_000,
            'currency' => 'NGN',
            'account_type' => WalletConst::GL,
            'email_subscribe' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
