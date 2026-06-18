<?php

declare(strict_types=1);

namespace App\DTO\SplitBill;

use App\Helpers\MoneyFormatter;

final readonly class InputDto
{
    /**
     * @param  ParticipantDto[]  $participants
     */
    public function __construct(
        public int $amountCents,
        public string $tipPercent,
        public array $participants
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            amountCents: MoneyFormatter::toCents((string) $data['amount']),
            tipPercent: (string) $data['tip_percent'],
            participants: array_map(
                fn (array $participant) => new ParticipantDto(
                    name: $participant['name'],
                    weight: (int) ($participant['weight'] ?? 1)
                ),
                $data['participants']
            ),
        );
    }
}
