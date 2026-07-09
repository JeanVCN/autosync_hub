<?php

use App\Http\Controllers\Api\IntegrationCallbackController;
use App\Http\Controllers\Api\VehicleController;
use Illuminate\Support\Facades\Route;

Route::apiResource('vehicles', VehicleController::class);
Route::post('vehicles/{vehicle}/sync', [VehicleController::class, 'sync']);
Route::get('vehicles/{vehicle}/integration-summary', [VehicleController::class, 'integrationSummary']);
Route::get('vehicles/{vehicle}/integration-logs', [VehicleController::class, 'integrationLogs']);
Route::post('integration-callbacks', IntegrationCallbackController::class);
