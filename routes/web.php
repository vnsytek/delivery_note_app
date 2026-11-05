<?php

use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CustomerPageController;
use App\Http\Controllers\DeliveryNoteController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:login');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/', CustomerPageController::class)->name('customers.index');

    Route::prefix('delivery-notes')->name('delivery-notes.')->group(function () {
        Route::get('/', [DeliveryNoteController::class, 'index'])->name('index');
        Route::get('/{delivery_note}', [DeliveryNoteController::class, 'show'])->name('show');
        Route::put('/{delivery_note}', [DeliveryNoteController::class, 'update'])->name('update');
        Route::get('/{delivery_note}/print', [DeliveryNoteController::class, 'print'])->name('print');
    });

    // Các endpoint JSON cho giao diện (giữ nguyên đường dẫn của bản Python cũ).
    Route::prefix('api')->name('api.')->group(function () {
        Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
        Route::get('/customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');
        Route::put('/overrides/{customer}', [CustomerController::class, 'updateNames'])->name('customers.names.update');
    });
});
