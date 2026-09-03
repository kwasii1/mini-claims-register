<?php

namespace Database\Factories;

use App\Models\Claim;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'claim_id' => Claim::factory(),
            'payment_date' => fake()->dateTimeBetween('-1 year', 'now'),
            'amount' => fake()->numberBetween(1000, 500000),
            'currency' => fake()->randomElement(['USD', 'GBP', 'EUR', 'GHS']),
            'fx_rate_snapshot' => fake()->randomFloat(6, 0.5, 2.0),
        ];
    }
}
