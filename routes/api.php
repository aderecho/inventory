<?php

use App\Http\Controllers\InventoryController;
use App\Http\Controllers\UPSSOController;
use App\Http\Controllers\API\ApiEmbedController;
use App\Http\Controllers\API\QrScanController;
use App\Http\Controllers\mobileAPI\AuthController;
use App\Http\Controllers\mobileAPI\InspectionController;
use App\Http\Controllers\Auth\GoogleController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

Route::prefix('v1')
    ->as('api.v1.')
    ->group(function () {
        Route::post('/auth/up/sso', [UPSSOController::class, 'ssoRedirect']);
        Route::post('/embed-token', [ApiEmbedController::class, 'issueToken']);

        Route::middleware('api.token')->group(function () {
            Route::get('/inventory', [InventoryController::class, 'apiIndex']);
            Route::get('/inventory/{id}', [InventoryController::class, 'apiShow']);
        });

        // Mobile
        Route::post('/login', [AuthController::class, 'login']);
        Route::middleware('auth:sanctum')->group(function () {
            Route::get('/me', function (Request $request) {
                return response()->json($request->user());
            });
            Route::prefix('inspection')->group(function () {
                Route::get('/', [InspectionController::class, 'index'])
                    ->name('inspection.index');

                Route::post('/', [InspectionController::class, 'store'])
                    ->name('inspection.store');

                Route::get('/item/{itemId}/history', [InspectionController::class, 'getItemInspectionHistory'])
                    ->name('inspection.history');

                Route::get('/stats', [InspectionController::class, 'getStats'])
                    ->name('inspection.stats');

                Route::post('/batch', [InspectionController::class, 'storeBatch']);

                Route::get('/conditions', [InspectionController::class, 'conditions'])
                    ->name('inspection.conditions');

                Route::get('/{id}', [InspectionController::class, 'show'])
                    ->name('inspection.show'); // must stay last
            });
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::post('/qr-scan', [QrScanController::class, 'store']);
        });
    });
