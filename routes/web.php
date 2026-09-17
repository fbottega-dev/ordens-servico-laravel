<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ServiceOrderController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect(auth()->check() ? '/orders' : '/login'));
Route::middleware('guest')->group(function () {
    Route::view('/login', 'auth')->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
});
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/orders', [ServiceOrderController::class, 'index'])->name('orders.index');
    Route::post('/orders', [ServiceOrderController::class, 'store'])->name('orders.store');
    Route::get('/orders/{order}', [ServiceOrderController::class, 'show'])->name('orders.show');
    Route::post('/orders/{order}/transition', [ServiceOrderController::class, 'transition'])->name('orders.transition');
});
