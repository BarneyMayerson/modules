<?php

use Modules\Order\CompleteOrder;
use Modules\Payment\PaymentSucceeded;
use Modules\Product\Events\DecreaseProductStock;

it("has listeners", function () {
    Event::fake();

    Event::assertListening(
        PaymentSucceeded::class,
        DecreaseProductStock::class
    );
    Event::assertListening(PaymentSucceeded::class, CompleteOrder::class);
});
