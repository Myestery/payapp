<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Withdrawal>
 */
class WithdrawalFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'account_id' => 1,
            'bank_name' => $this->faker->word,
            'account_name' => $this->faker->name,
            'account_number' => $this->faker->bankAccountNumber,
            'bank_code' => $this->faker->word,
            'amount' => $this->faker->randomFloat(2, 1000, 100000),
            'provider' => $this->faker->word,
            'status' => $this->faker->word,
            'reference' => $this->faker->uuid,
            'session_id' => $this->faker->uuid,
            'wallet_debited' => $this->faker->boolean,
            'value_given' => $this->faker->boolean,
            'response_code' => $this->faker->word,
            'response_message' => $this->faker->sentence,
        ];
    }
}
