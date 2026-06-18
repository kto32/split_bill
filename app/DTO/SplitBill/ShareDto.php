<?php

declare(strict_types=1);

namespace App\DTO\SplitBill;

use App\Helpers\MoneyFormatter;

final readonly class ShareDto
{
    public function __construct(
        public string $name,
        public int $amountCents,
    ) {}

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'amount' => MoneyFormatter::fromCents($this->amountCents),
        ];
    }
}
