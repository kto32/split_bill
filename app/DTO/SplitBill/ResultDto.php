<?php

declare(strict_types=1);

namespace App\DTO\SplitBill;

use App\Helpers\MoneyFormatter;

final readonly class ResultDto
{
    /**
     * @param  ShareDto[]  $shares
     */
    public function __construct(
        public int $totalCents,
        public array $shares
    ) {}

    public function toArray(): array
    {
        return [
            'total' => MoneyFormatter::fromCents($this->totalCents),
            'shares' => array_map(
                fn (ShareDto $share) => $share->toArray(),
                $this->shares,
            ),
        ];
    }
}
