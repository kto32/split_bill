<?php

declare(strict_types=1);

namespace App\DTO\SplitBill;

final readonly class ResultDto
{
    /**
     * @param  ShareDto[]  $shares
     */
    public function __construct(
        public int $totalCents,
        public array $shares
    ) {}
}
