<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\SplitBillRequest;
use App\Http\Resources\SplitBillResource;
use App\Services\SplitBill\SplitBillService;

class SplitBillController extends Controller
{
    public function __invoke(SplitBillRequest $request, SplitBillService $service): SplitBillResource
    {
        $resultDto = $service->split($request->toDto());

        return new SplitBillResource($resultDto);
    }
}
