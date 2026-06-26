<?php

use App\Http\Controllers\AdvisorController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

// Rutas públicas del webhook de WhatsApp
Route::get('/webhook', [WebhookController::class, 'verify']);
Route::post('/webhook', [WebhookController::class, 'receive']);

// Redirección raíz
Route::get('/', function () {
    return redirect()->route('login');
});

// Rutas autenticadas
Route::middleware(['auth'])->group(function () {

    // Admin + Sistema: dashboard, asesores, mensajes, usuarios
    Route::middleware(['role:sistema|admin'])->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::post('/assignments/{assignment}/assign', [DashboardController::class, 'assign'])->name('assignments.assign');
        Route::get('/mensajes', [DashboardController::class, 'messages'])->name('admin.messages');
        Route::resource('/advisors', AdvisorController::class)->except(['show']);
        Route::resource('/users', UserController::class)->except(['show']);
    });

    // Asesor: chat con clientes
    Route::middleware(['role:asesor'])->group(function () {
        Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
        Route::get('/chat/messages', [ChatController::class, 'messages'])->name('chat.messages');
        Route::post('/chat/send', [ChatController::class, 'send'])->name('chat.send');
        Route::post('/chat/accept', [ChatController::class, 'accept'])->name('chat.accept');
        Route::post('/chat/close', [ChatController::class, 'close'])->name('chat.close');
    });

    // Perfil (ambos roles)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
