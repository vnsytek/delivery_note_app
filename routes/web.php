<?php

use App\Http\Controllers\AccentWordController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CustomerPageController;
use App\Http\Controllers\DeliveryNoteController;
use App\Http\Controllers\OrderController;
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

    // Đơn đặt hàng (chỉ xem): chụp gửi khách trước, in phiếu giao sau.
    Route::prefix('orders')->name('orders.')->group(function () {
        Route::get('/', [OrderController::class, 'index'])->name('index');
        Route::get('/{order}', [OrderController::class, 'show'])->name('show');
    });

    // Từ điển sửa dấu: chọn cách viết đúng cho từ ERP bị mất dấu, một lần cho mọi nơi (chỉ ghi vào MySQL của app).
    Route::prefix('accents')->name('accents.')->group(function () {
        Route::get('/', [AccentWordController::class, 'index'])->name('index');
        Route::post('/confirm-suggested', [AccentWordController::class, 'confirmSuggested'])->name('confirm-suggested');
        Route::post('/refresh', [AccentWordController::class, 'refresh'])->name('refresh');
        Route::put('/{accentWord}', [AccentWordController::class, 'update'])->name('update');
    });

    // Các endpoint JSON cho giao diện (giữ nguyên đường dẫn của bản Python cũ).
    Route::prefix('api')->name('api.')->group(function () {
        Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
        Route::get('/customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');
        Route::put('/overrides/{customer}', [CustomerController::class, 'updateNames'])->name('customers.names.update');
    });
});
