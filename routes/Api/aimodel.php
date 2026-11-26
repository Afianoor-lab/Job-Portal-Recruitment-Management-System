<?php

use App\Http\Controllers\Api\OpenRouterModelController;
use App\Http\Middleware\IPAgent;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {


    Route::middleware('auth.token')->group(function () {
        Route::post('/model', [OpenRouterModelController::class, 'store']);
        Route::get('/models', [OpenRouterModelController::class, 'index']) ;

    });
});