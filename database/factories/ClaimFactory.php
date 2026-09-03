<?php

namespace Database\Factories;

use App\Models\Claim;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Claim>
 */
class ClaimFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $lossDate = fake()->dateTimeBetween('-2 years', 'now');
        $dateNotified = fake()->dateTimeBetween($lossDate, 'now');
        $currency = fake()->randomElement(['USD', 'GBP', 'EUR', 'GHS']);
        $estimatedLoss = fake()->numberBetween(10000, 5000000);

        return [
            'policy_number' => strtoupper(fake()->bothify('??-####-####')),
            'insured_name' => fake()->company(),
            'loss_date' => $lossDate,
            'date_notified' => $dateNotified,
            'loss_nature' => fake()->randomElement([
                'Fire damage',
                'Flood damage',
                'Theft',
                'Vehicle collision',
                'Storm damage',
                'Liability claim',
                'Professional indemnity',
                'Business interruption',
            ]),
            'reserve_currency' => $currency,
            'estimated_loss_amount' => $estimatedLoss,
            'approved_amount' => null,
        ];
    }

    /**
     * Claim with an approved amount (settled state).
     */
    public function settled(): static
    {
        return $this->state(fn (array $attributes) => [
            'approved_amount' => fake()->numberBetween(
                (int) ($attributes['estimated_loss_amount'] * 0.5),
                (int) ($attributes['estimated_loss_amount'] * 1.5),
            ),
        ]);
    }

    /**
     * Claim with no approved amount (reserved state).
     */
    public function reserved(): static
    {
        return $this->state(fn () => [
            'approved_amount' => null,
        ]);
    }
}
