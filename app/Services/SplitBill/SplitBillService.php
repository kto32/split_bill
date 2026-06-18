<?php

declare(strict_types=1);

namespace App\Services\SplitBill;

use App\DTO\SplitBill\InputDto;
use App\DTO\SplitBill\ParticipantDto;
use App\DTO\SplitBill\ResultDto;
use App\DTO\SplitBill\ShareDto;
use App\Helpers\PercentFormatter;

final class SplitBillService
{
    public function split(InputDto $input): ResultDto
    {
        $totalCents = $input->amountCents + $this->calculateTipCents($input->amountCents, $input->tipPercent);
        $totalWeight = array_reduce(
            $input->participants,
            fn (int $carry, ParticipantDto $participant) => $carry + $participant->weight,
            0
        );

        $tempParticipants = [];
        $baseCents = 0;

        foreach ($input->participants as $index => $participant) {
            $calcBaseCents = intdiv($totalCents * $participant->weight, $totalWeight);
            $tempParticipants[] = [
                'index' => $index,
                'name' => $participant->name,
                'baseCents' => $calcBaseCents,
                'remainder' => ($totalCents * $participant->weight) % $totalWeight,
            ];

            $baseCents += $calcBaseCents;
        }

        $leftoverCents = $totalCents - $baseCents;

        usort($tempParticipants, function (array $a, array $b): int {
            return $b['remainder'] <=> $a['remainder']
                ?: $a['index'] <=> $b['index'];
        });

        for ($i = 0; $i < $leftoverCents; $i++) {
            $tempParticipants[$i]['baseCents']++;
        }

        usort($tempParticipants, fn (array $a, array $b): int => $a['index'] <=> $b['index']);

        $shares = array_map(
            fn (array $participant) => new ShareDto(
                name: $participant['name'],
                amountCents: $participant['baseCents']
            ),
            $tempParticipants
        );

        return new ResultDto(
            totalCents: $totalCents,
            shares: $shares,
        );
    }

    private function calculateTipCents(int $amountCents, string $tipPercent): int
    {
        $basisPoints = PercentFormatter::toBasisPoints($tipPercent);

        return intdiv(($amountCents * $basisPoints) + 5000, 10000);
    }
}
