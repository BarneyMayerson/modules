<?php

use Illuminate\Support\Facades\Route;
use Modules\Order\Checkout\CheckoutController;
use Modules\Order\Order;

Route::middleware("auth")->group(function () {
    Route::post("checkout", CheckoutController::class)->name("order::checkout");

    Route::get("order/{order}", fn(Order $order) => $order)->name("order.show");
});
