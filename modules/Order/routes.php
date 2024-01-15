<?php

use Illuminate\Support\Facades\Route;
use Modules\Order\Http\Controllers\CheckoutController;
use Modules\Order\Models\Order;

Route::middleware("auth")->group(function () {
    Route::post("checkout", CheckoutController::class)->name("order::checkout");

    Route::get("order/{order}", fn(Order $order) => $order)->name("order.show");
});
