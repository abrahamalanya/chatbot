<?php

use App\Http\Controllers\ChatController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/webhook', [WebhookController::class, 'verify']);
Route::post('/webhook', [WebhookController::class, 'receive']);

Route::get('/chat', [ChatController::class, 'index']);
Route::get('/chat/messages', [ChatController::class, 'messages']);
Route::post('/chat/send', [ChatController::class, 'send']);