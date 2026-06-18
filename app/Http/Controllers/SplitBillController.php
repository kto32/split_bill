<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\SplitBillRequest;
use App\Services\SplitBill\SplitBillService;
use Illuminate\Http\JsonResponse;

class SplitBillController extends Controller
{
    public function __invoke(SplitBillRequest $request, SplitBillService $service): JsonResponse
    {
        $resultDto = $service->split($request->toDto());

        return response()->json($resultDto->toArray());
    }
}
