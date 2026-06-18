<?php

declare(strict_types=1);

namespace App\DTO\SplitBill;

final readonly class ShareDto
{
    public function __construct(
        public string $name,
        public int $amountCents,
    ) {}
}
