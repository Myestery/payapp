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

        DB::table('accounts')->insert([
            'balance' => 0.00,
            'name' => 'FEES GL',
            'transaction_limit' => 1_000_000_000_000_000,
            'currency' => 'NGN',
            'account_type' => WalletConst::GL,
            'email_subscribe' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        User::factory(10)->create();
    }
}
