<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ApiTokenController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DeviceController;
use App\Http\Controllers\Api\FuelSaleController;
use App\Http\Controllers\Api\PumpController;
use App\Http\Controllers\Api\StationController;
use App\Http\Controllers\Api\TankReadingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — station integration endpoints + Sanctum auth
|--------------------------------------------------------------------------
*/

Route::middleware('throttle:api')->post('/token', [ApiTokenController::class, 'store']);

Route::middleware('auth:sanctum', 'throttle:api')->group(function () {
    // Station data endpoints.
    Route::get('/stations', [StationController::class, 'index']);
    Route::get('/stations/{station}', [StationController::class, 'show']);
    Route::get('/stations/{station}/pumps', [PumpController::class, 'index']);
    Route::get('/stations/{station}/transactions', [FuelSaleController::class, 'stationTransactions']);
    Route::get('/stations/{station}/tanks', [TankReadingController::class, 'stationTanks']);

    // Live device heartbeat.
    Route::post('/integration/device-heartbeat', [DeviceController::class, 'heartbeat']);

    // Fuel transaction ingestion (idempotent).
    Route::post('/integration/fuel-transactions', [FuelSaleController::class, 'ingest']);

    // Tank readings ingestion.
    Route::post('/integration/tank-readings', [TankReadingController::class, 'store']);

    // Pump status ingestion.
    Route::post('/integration/pump-status', [PumpController::class, 'updateStatus']);
});
