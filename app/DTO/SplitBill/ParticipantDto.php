<?php

declare(strict_types=1);

namespace App\DTO\SplitBill;

final readonly class ParticipantDto
{
    public function __construct(
        public string $name,
        public int $weight
    ) {}
}
