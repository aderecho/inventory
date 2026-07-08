<?php

use App\Http\Controllers\InventoryController;
use App\Http\Controllers\UPSSOController;
use App\Http\Controllers\API\ApiEmbedController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')
    ->as('api.v1.')
    ->group(function () {
        Route::post('/auth/up/sso', [UPSSOController::class, 'ssoRedirect']);
        Route::post('/embed-token', [ApiEmbedController::class, 'issueToken']);
        Route::get('/inventory', [InventoryController::class, 'apiIndex']);
        Route::get('/inventory/{id}', [InventoryController::class, 'apiShow']);
    });