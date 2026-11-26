<?php

use App\Http\Controllers\Api\ConversationController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\OpenRouterModelController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\LibraryController;
use Illuminate\Support\Facades\Route;

//Route::middleware('auth:sanctum')->group(function (): void {
Route::prefix('v1')->group(function () {

Route::middleware('auth.token')->group(function () {
    Route::get('/user', [UserController::class, 'show']);
    Route::put('/user/profile', [UserController::class, 'updateProfile']);
    Route::post('/users/avatar', [ProfileController::class, 'updateAvatar']);
    Route::get('/users/avatar', [ProfileController::class, 'show']);
    Route::put('/user/settings', [UserController::class, 'updateSettings']);
    Route::delete('/user', [UserController::class, 'destroy']);
    Route::delete('/settings/api-key', [UserController::class, 'deleteApiKey']);
    Route::put('/settings/passcode', [UserController::class, 'updatePasscode']);
    //Route::get('/models', OpenRouterModelController::class);
    Route::apiResource('projects', ProjectController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::get('/chats', [ConversationController::class, 'index']);
    Route::post('/chats', [ConversationController::class, 'store']);
    Route::get('/chats/{conversation}', [ConversationController::class, 'show']);
    Route::patch('/chats/{conversation}', [ConversationController::class, 'update']);
    Route::delete('/chats/{conversation}', [ConversationController::class, 'destroy']);
    Route::delete('/chats', [ConversationController::class, 'destroyAll']);
    Route::post('/chats/{conversation}/duplicate', [ConversationController::class, 'duplicate']);
    Route::post('/chats/{conversation}/lock', [ConversationController::class, 'lock']);
    Route::post('/chats/{conversation}/unlock', [ConversationController::class, 'unlock']);
    Route::get('/conversations', [ConversationController::class, 'index']);
    Route::post('/conversations', [ConversationController::class, 'store']);
    Route::get('/conversations/{conversation}', [ConversationController::class, 'show']);
    Route::delete('/conversations/{conversation}', [ConversationController::class, 'destroy']);


    Route::post('/chat/{id}/message', [MessageController::class, 'chat']);
    Route::get('/chat/{id}/message', [MessageController::class, 'show']);
    //Route::post('/chat/{id}/message', [MessageController::class, 'chat']);
    Route::match(['put', 'patch'],'/chat/{chatId}/message/{messageId}', [MessageController::class, 'updateMessage']); // API #36: Message Edit
    Route::delete('/chat/{chatId}/message/{messageId}', [MessageController::class, 'deleteMessage']); // API #37: Message Delete


  

    Route::post('library/', [LibraryController::class, 'store']);
    Route::get('library/', [LibraryController::class, 'index']);
    Route::get('library/{id}', [LibraryController::class, 'show']);
    Route::match(['put', 'patch'], 'library/{id}', [LibraryController::class, 'update']);
    Route::delete('library/{id}', [LibraryController::class, 'destroy']);

    });

    });
//});
