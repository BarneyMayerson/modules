<?php

use Illuminate\Support\Facades\Event;
use Modules\Order\Checkout\OrderStarted;
use Modules\Order\Checkout\SendOrderConfirmationEmail;
use Modules\Payment\PayOrder;

it("has listeners", function () {
    Event::fake();

    Event::assertListening(
        OrderStarted::class,
        SendOrderConfirmationEmail::class
    );
    Event::assertListening(OrderStarted::class, PayOrder::class);
});
