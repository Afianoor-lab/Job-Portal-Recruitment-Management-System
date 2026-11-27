<?php

use App\Http\Controllers\Api\OpenRouterModelController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Middleware\IPAgent;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {


    Route::middleware('auth.token')->group(function () {
        
        Route::post('/users/avatar', [SettingController::class, 'updateAvatar']);
        Route::get('/users/avatar', [SettingController::class, 'show']);
        Route::delete('/users/avatar/remove', [SettingController::class, 'remove']);
        Route::post('/change-password', [SettingController::class, 'changePassword']);

        

    });
});