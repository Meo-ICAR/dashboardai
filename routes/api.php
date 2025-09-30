<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\LeadController;
use App\Http\Controllers\Api\CallController;
use App\Http\Controllers\ChartController;

Route::middleware('api')->group(function () {
    Route::apiResource('leads', LeadController::class);
    Route::apiResource('calls', CallController::class);

    Route::post('/generate-chart', [ChartController::class, 'generate']);
    Route::post('/drill-down', [ChartController::class, 'drillDown']);
    Route::post('/save-chart', [ChartController::class, 'save']);
    Route::get('/saved-charts', [ChartController::class, 'list']);
    Route::post('/rerun-chart/{id}', [ChartController::class, 'rerun']);
});
