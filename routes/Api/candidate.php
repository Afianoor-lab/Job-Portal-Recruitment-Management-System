<?php

use App\Http\Controllers\Api\CandidateProfileController;
use App\Http\Middleware\EnsureIsCandidate;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {


    Route::middleware('auth.token')->group(function () {
        Route::middleware(EnsureIsCandidate::class)->group(function () {
        
        Route::post('/candidate/profile', [CandidateProfileController::class, 'save']);
        Route::get('/candidate/profile', [CandidateProfileController::class, 'show']);
        Route::delete('/candidate/profile', [CandidateProfileController::class, 'delete']);

        Route::post('/candidate/cv', [CandidateProfileController::class, 'upload']);
        Route::get('/candidate/cv/{id}', [CandidateProfileController::class, 'showcv']);
        Route::delete('/candidate/cv/{id}', [CandidateProfileController::class, 'remove']);
        Route::get('/candidate/cvs', [CandidateProfileController::class, 'cvlist']);
        Route::post('/candidate/cv/current/{id}', [CandidateProfileController::class, 'setCurrentCv']);
        
        });

        

    });
});