<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\DTO\SplitBill\ResultDto;
use App\DTO\SplitBill\ShareDto;
use App\Helpers\MoneyFormatter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class SplitBillResource extends JsonResource
{
    public static $wrap = null;

    public function __construct(ResultDto $resource)
    {
        parent::__construct($resource);
    }

    /**
     * Transform the resource into an array.
     *
     * @return array{total: string, shares: array<int, array{name: string, amount: string}>}
     */
    public function toArray(Request $request): array
    {
        /** @var ResultDto $result */
        $result = $this->resource;

        return [
            'total' => MoneyFormatter::fromCents($result->totalCents),
            'shares' => array_map(
                fn (ShareDto $share): array => [
                    'name' => $share->name,
                    'amount' => MoneyFormatter::fromCents($share->amountCents),
                ],
                $result->shares,
            ),
        ];
    }
}
