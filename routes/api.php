<?php

declare(strict_types=1);

use App\Http\Controllers\SplitBillController;
use Illuminate\Support\Facades\Route;

Route::post('/split', SplitBillController::class);
