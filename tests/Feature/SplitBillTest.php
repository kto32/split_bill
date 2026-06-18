<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

final class SplitBillTest extends TestCase
{
    public function test_it_splits_evenly_without_remainder(): void
    {
        $response = $this->postJson('/api/split', [
            'amount' => '100.00',
            'tip_percent' => '0',
            'participants' => [
                ['name' => 'Аня'],
                ['name' => 'Боря'],
            ],
        ]);

        $response
            ->assertOk()
            ->assertExactJson([
                'total' => '100.00',
                'shares' => [
                    ['name' => 'Аня', 'amount' => '50.00'],
                    ['name' => 'Боря', 'amount' => '50.00'],
                ],
            ]);
    }

    public function test_it_distributes_remainder_deterministically_and_preserves_total(): void
    {
        $response = $this->postJson('/api/split', [
            'amount' => '100.00',
            'tip_percent' => '0',
            'participants' => [
                ['name' => 'Аня'],
                ['name' => 'Боря'],
                ['name' => 'Вася'],
            ],
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('total', '100.00')
            ->assertJsonPath('shares.0.amount', '33.34')
            ->assertJsonPath('shares.1.amount', '33.33')
            ->assertJsonPath('shares.2.amount', '33.33');

        $this->assertSharesSumEqualsTotal($response->json());
    }

    public function test_it_applies_tip_percent_before_splitting(): void
    {
        $response = $this->postJson('/api/split', [
            'amount' => '100.00',
            'tip_percent' => '15',
            'participants' => [
                ['name' => 'Аня'],
                ['name' => 'Боря'],
                ['name' => 'Вася'],
            ],
        ]);

        $response
            ->assertOk()
            ->assertExactJson([
                'total' => '115.00',
                'shares' => [
                    ['name' => 'Аня', 'amount' => '38.34'],
                    ['name' => 'Боря', 'amount' => '38.33'],
                    ['name' => 'Вася', 'amount' => '38.33'],
                ],
            ]);

        $this->assertSharesSumEqualsTotal($response->json());
    }

    public function test_it_respects_participant_weights(): void
    {
        $response = $this->postJson('/api/split', [
            'amount' => '100.00',
            'tip_percent' => '0',
            'participants' => [
                ['name' => 'Аня', 'weight' => 1],
                ['name' => 'Боря', 'weight' => 3],
            ],
        ]);

        $response
            ->assertOk()
            ->assertExactJson([
                'total' => '100.00',
                'shares' => [
                    ['name' => 'Аня', 'amount' => '25.00'],
                    ['name' => 'Боря', 'amount' => '75.00'],
                ],
            ]);

        $this->assertSharesSumEqualsTotal($response->json());
    }

    public function test_it_handles_fractional_tip_weights_and_remainders_together(): void
    {
        $response = $this->postJson('/api/split', [
            'amount' => '999.99',
            'tip_percent' => '12.5',
            'participants' => [
                ['name' => 'Аня', 'weight' => 2],
                ['name' => 'Боря', 'weight' => 3],
                ['name' => 'Вася', 'weight' => 5],
            ],
        ]);

        $response
            ->assertOk()
            ->assertExactJson([
                'total' => '1124.99',
                'shares' => [
                    ['name' => 'Аня', 'amount' => '225.00'],
                    ['name' => 'Боря', 'amount' => '337.50'],
                    ['name' => 'Вася', 'amount' => '562.49'],
                ],
            ]);

        $this->assertSharesSumEqualsTotal($response->json());
    }

    public function test_it_handles_large_amounts_with_weighted_remainder_tie_break(): void
    {
        $response = $this->postJson('/api/split', [
            'amount' => '1234567.89',
            'tip_percent' => '17.25',
            'participants' => [
                ['name' => 'Аня', 'weight' => 1],
                ['name' => 'Боря', 'weight' => 2],
                ['name' => 'Вася', 'weight' => 7],
            ],
        ]);

        $response
            ->assertOk()
            ->assertExactJson([
                'total' => '1447530.85',
                'shares' => [
                    ['name' => 'Аня', 'amount' => '144753.09'],
                    ['name' => 'Боря', 'amount' => '289506.17'],
                    ['name' => 'Вася', 'amount' => '1013271.59'],
                ],
            ]);

        $this->assertSharesSumEqualsTotal($response->json());
    }

    public function test_it_accepts_maximum_number_of_participants(): void
    {
        $participants = array_map(
            fn (int $number): array => ['name' => 'Участник '.$number],
            range(1, 50)
        );

        $response = $this->postJson('/api/split', [
            'amount' => '1.00',
            'tip_percent' => '0',
            'participants' => $participants,
        ]);

        $response
            ->assertOk()
            ->assertJsonCount(50, 'shares')
            ->assertJsonPath('total', '1.00')
            ->assertJsonPath('shares.0.amount', '0.02')
            ->assertJsonPath('shares.49.amount', '0.02');

        $this->assertSharesSumEqualsTotal($response->json());
    }

    public function test_it_uses_default_weight_when_weight_is_missing(): void
    {
        $response = $this->postJson('/api/split', [
            'amount' => '12.00',
            'tip_percent' => '0',
            'participants' => [
                ['name' => 'Аня'],
                ['name' => 'Боря', 'weight' => 2],
                ['name' => 'Вася'],
            ],
        ]);

        $response
            ->assertOk()
            ->assertExactJson([
                'total' => '12.00',
                'shares' => [
                    ['name' => 'Аня', 'amount' => '3.00'],
                    ['name' => 'Боря', 'amount' => '6.00'],
                    ['name' => 'Вася', 'amount' => '3.00'],
                ],
            ]);

        $this->assertSharesSumEqualsTotal($response->json());
    }

    public function test_it_handles_one_cent_amount_with_more_participants_than_cents(): void
    {
        $response = $this->postJson('/api/split', [
            'amount' => '0.01',
            'tip_percent' => '0',
            'participants' => [
                ['name' => 'Аня'],
                ['name' => 'Боря'],
                ['name' => 'Вася'],
            ],
        ]);

        $response
            ->assertOk()
            ->assertExactJson([
                'total' => '0.01',
                'shares' => [
                    ['name' => 'Аня', 'amount' => '0.01'],
                    ['name' => 'Боря', 'amount' => '0.00'],
                    ['name' => 'Вася', 'amount' => '0.00'],
                ],
            ]);

        $this->assertSharesSumEqualsTotal($response->json());
    }

    public function test_it_handles_one_hundred_percent_tip(): void
    {
        $response = $this->postJson('/api/split', [
            'amount' => '10.00',
            'tip_percent' => '100',
            'participants' => [
                ['name' => 'Аня'],
                ['name' => 'Боря'],
            ],
        ]);

        $response
            ->assertOk()
            ->assertExactJson([
                'total' => '20.00',
                'shares' => [
                    ['name' => 'Аня', 'amount' => '10.00'],
                    ['name' => 'Боря', 'amount' => '10.00'],
                ],
            ]);

        $this->assertSharesSumEqualsTotal($response->json());
    }

    public function test_it_rounds_fractional_tip_half_up_to_nearest_cent(): void
    {
        $response = $this->postJson('/api/split', [
            'amount' => '0.01',
            'tip_percent' => '50',
            'participants' => [
                ['name' => 'Аня'],
            ],
        ]);

        $response
            ->assertOk()
            ->assertExactJson([
                'total' => '0.02',
                'shares' => [
                    ['name' => 'Аня', 'amount' => '0.02'],
                ],
            ]);

        $this->assertSharesSumEqualsTotal($response->json());
    }

    public function test_it_keeps_input_order_as_tie_breaker_for_equal_remainders(): void
    {
        $response = $this->postJson('/api/split', [
            'amount' => '0.02',
            'tip_percent' => '0',
            'participants' => [
                ['name' => 'Аня'],
                ['name' => 'Боря'],
                ['name' => 'Вася'],
            ],
        ]);

        $response
            ->assertOk()
            ->assertExactJson([
                'total' => '0.02',
                'shares' => [
                    ['name' => 'Аня', 'amount' => '0.01'],
                    ['name' => 'Боря', 'amount' => '0.01'],
                    ['name' => 'Вася', 'amount' => '0.00'],
                ],
            ]);

        $this->assertSharesSumEqualsTotal($response->json());
    }

    public function test_it_returns_validation_errors_as_json(): void
    {
        $invalidCases = [
            [
                'payload' => [
                    'amount' => '0',
                    'tip_percent' => '10',
                    'participants' => [['name' => 'Аня']],
                ],
                'errors' => ['amount'],
            ],
            [
                'payload' => [
                    'amount' => '100.00',
                    'tip_percent' => '101',
                    'participants' => [['name' => 'Аня']],
                ],
                'errors' => ['tip_percent'],
            ],
            [
                'payload' => [
                    'amount' => '100.00',
                    'tip_percent' => '10',
                    'participants' => [],
                ],
                'errors' => ['participants'],
            ],
            [
                'payload' => [
                    'amount' => '100.00',
                    'tip_percent' => '10',
                    'participants' => [['name' => 'Аня', 'weight' => 0]],
                ],
                'errors' => ['participants.0.weight'],
            ],
            [
                'payload' => [
                    'amount' => '100.00',
                    'tip_percent' => '10',
                    'participants' => array_map(
                        fn (int $number): array => ['name' => 'Участник '.$number],
                        range(1, 51)
                    ),
                ],
                'errors' => ['participants'],
            ],
        ];

        foreach ($invalidCases as $case) {
            $response = $this->postJson('/api/split', $case['payload']);

            $response
                ->assertUnprocessable()
                ->assertJsonPath('message', 'Validation failed.')
                ->assertJsonValidationErrors($case['errors']);
        }
    }

    /**
     * @param  array{total: string, shares: array<int, array{name: string, amount: string}>}  $payload
     */
    private function assertSharesSumEqualsTotal(array $payload): void
    {
        $sharesSum = array_sum(array_map(
            fn (array $share): int => $this->decimalMoneyToCents($share['amount']),
            $payload['shares']
        ));

        $this->assertSame($this->decimalMoneyToCents($payload['total']), $sharesSum);
    }

    private function decimalMoneyToCents(string $amount): int
    {
        [$units, $cents] = array_pad(explode('.', $amount, 2), 2, '0');

        return ((int) $units * 100) + (int) str_pad($cents, 2, '0');
    }
}
