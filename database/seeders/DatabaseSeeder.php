<?php

namespace Database\Seeders;

use App\Models\Claim;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        User::factory()->create([
            'name' => 'Claims Reviewer',
            'email' => 'reviewer@example.com',
            'password' => bcrypt('password'),
        ]);

        // Reserved claim — no approved amount, no payments
        Claim::factory()->reserved()->create([
            'policy_number' => 'POL-0001-0001',
            'insured_name' => 'Acme Corp',
            'reserve_currency' => 'USD',
            'estimated_loss_amount' => 2500000,
        ]);

        // Reserved claim — no approved amount, has a payment
        $claim2 = Claim::factory()->reserved()->create([
            'policy_number' => 'POL-0002-0002',
            'insured_name' => 'Globex Industries',
            'reserve_currency' => 'GBP',
            'estimated_loss_amount' => 1800000,
        ]);

        Payment::factory()->create([
            'claim_id' => $claim2->id,
            'payment_date' => now()->subDays(10),
            'amount' => 500000,
            'currency' => 'GBP',
            'fx_rate_snapshot' => '1.000000',
        ]);

        // Settled claim — approved amount matches payments exactly
        $claim3 = Claim::factory()->settled()->create([
            'policy_number' => 'POL-0003-0003',
            'insured_name' => 'Initech Ltd',
            'reserve_currency' => 'USD',
            'estimated_loss_amount' => 750000,
            'approved_amount' => 750000,
        ]);

        Payment::factory()->create([
            'claim_id' => $claim3->id,
            'payment_date' => now()->subDays(5),
            'amount' => 750000,
            'currency' => 'USD',
            'fx_rate_snapshot' => '1.000000',
        ]);

        // Settled, payment outstanding — approved but only partially paid
        $claim4 = Claim::factory()->settled()->create([
            'policy_number' => 'POL-0004-0004',
            'insured_name' => 'Umbrella Insurance',
            'reserve_currency' => 'EUR',
            'estimated_loss_amount' => 3200000,
            'approved_amount' => 3000000,
        ]);

        Payment::factory()->create([
            'claim_id' => $claim4->id,
            'payment_date' => now()->subDays(3),
            'amount' => 1500000,
            'currency' => 'EUR',
            'fx_rate_snapshot' => '1.000000',
        ]);

        // Cross-currency: USD claim with a GBP payment
        $claim5 = Claim::factory()->settled()->create([
            'policy_number' => 'POL-0005-0005',
            'insured_name' => 'Stark Industries',
            'reserve_currency' => 'USD',
            'estimated_loss_amount' => 4000000,
            'approved_amount' => 3800000,
        ]);

        Payment::factory()->create([
            'claim_id' => $claim5->id,
            'payment_date' => now()->subDays(7),
            'amount' => 2000000,
            'currency' => 'GBP',
            'fx_rate_snapshot' => '1.270000',
        ]);

        Payment::factory()->create([
            'claim_id' => $claim5->id,
            'payment_date' => now()->subDays(2),
            'amount' => 500000,
            'currency' => 'EUR',
            'fx_rate_snapshot' => '1.090000',
        ]);

        // Cross-currency: GBP claim with USD payment
        $claim6 = Claim::factory()->settled()->create([
            'policy_number' => 'POL-0006-0006',
            'insured_name' => 'Wayne Enterprises',
            'reserve_currency' => 'GBP',
            'estimated_loss_amount' => 2200000,
            'approved_amount' => 2000000,
        ]);

        Payment::factory()->create([
            'claim_id' => $claim6->id,
            'payment_date' => now()->subDays(4),
            'amount' => 1600000,
            'currency' => 'USD',
            'fx_rate_snapshot' => '0.790000',
        ]);

        // Overpaid claim (negative balance — still "Settled and paid")
        $claim7 = Claim::factory()->settled()->create([
            'policy_number' => 'POL-0007-0007',
            'insured_name' => 'Cyberdyne Systems',
            'reserve_currency' => 'USD',
            'estimated_loss_amount' => 1000000,
            'approved_amount' => 900000,
        ]);

        Payment::factory()->create([
            'claim_id' => $claim7->id,
            'payment_date' => now()->subDays(1),
            'amount' => 1100000,
            'currency' => 'USD',
            'fx_rate_snapshot' => '1.000000',
        ]);

        // Remaining claims — mix of statuses and currencies for the list view
        Claim::factory()->reserved()->create([
            'policy_number' => 'POL-0008-0008',
            'insured_name' => 'Massive Dynamic',
            'reserve_currency' => 'EUR',
            'estimated_loss_amount' => 890000,
        ]);

        Claim::factory()->settled()->create([
            'policy_number' => 'POL-0009-0009',
            'insured_name' => 'Oscorp',
            'reserve_currency' => 'GBP',
            'estimated_loss_amount' => 1500000,
            'approved_amount' => 1500000,
        ]);

        Claim::factory()->reserved()->create([
            'policy_number' => 'POL-0010-0010',
            'insured_name' => 'LexCorp',
            'reserve_currency' => 'USD',
            'estimated_loss_amount' => 670000,
        ]);

        Claim::factory()->settled()->create([
            'policy_number' => 'POL-0011-0011',
            'insured_name' => 'Wayne Corp',
            'reserve_currency' => 'EUR',
            'estimated_loss_amount' => 2100000,
            'approved_amount' => 2000000,
        ]);

        Claim::factory()->reserved()->create([
            'policy_number' => 'POL-0012-0012',
            'insured_name' => 'Hooli Inc',
            'reserve_currency' => 'GBP',
            'estimated_loss_amount' => 340000,
        ]);

        Claim::factory()->settled()->create([
            'policy_number' => 'POL-0013-0013',
            'insured_name' => 'Pied Piper',
            'reserve_currency' => 'USD',
            'estimated_loss_amount' => 560000,
            'approved_amount' => 500000,
        ]);

        Claim::factory()->reserved()->create([
            'policy_number' => 'POL-0014-0014',
            'insured_name' => 'Soylent Corp',
            'reserve_currency' => 'EUR',
            'estimated_loss_amount' => 1200000,
        ]);

        Claim::factory()->settled()->create([
            'policy_number' => 'POL-0015-0015',
            'insured_name' => 'Aperture Science',
            'reserve_currency' => 'GBP',
            'estimated_loss_amount' => 980000,
            'approved_amount' => 950000,
        ]);

        // Add a couple more payments to some of the remaining claims
        $claim9 = Claim::where('policy_number', 'POL-0009-0009')->first();
        Payment::factory()->create([
            'claim_id' => $claim9->id,
            'payment_date' => now()->subDays(6),
            'amount' => 750000,
            'currency' => 'GBP',
            'fx_rate_snapshot' => '1.000000',
        ]);

        $claim11 = Claim::where('policy_number', 'POL-0011-0011')->first();
        Payment::factory()->create([
            'claim_id' => $claim11->id,
            'payment_date' => now()->subDays(8),
            'amount' => 1000000,
            'currency' => 'EUR',
            'fx_rate_snapshot' => '1.000000',
        ]);

        $claim13 = Claim::where('policy_number', 'POL-0013-0013')->first();
        Payment::factory()->create([
            'claim_id' => $claim13->id,
            'payment_date' => now()->subDays(2),
            'amount' => 300000,
            'currency' => 'GBP',
            'fx_rate_snapshot' => '1.270000',
        ]);

        $claim15 = Claim::where('policy_number', 'POL-0015-0015')->first();
        Payment::factory()->create([
            'claim_id' => $claim15->id,
            'payment_date' => now()->subDays(3),
            'amount' => 450000,
            'currency' => 'USD',
            'fx_rate_snapshot' => '0.790000',
        ]);
    }
}
