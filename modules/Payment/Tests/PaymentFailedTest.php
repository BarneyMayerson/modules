<?php

use Modules\Order\Checkout\MarkOrderAsFailed;
use Modules\Order\Checkout\NotifyUserOfPaymentFailure;
use Modules\Payment\PaymentFailed;

it("has lesteners", function () {
    Event::fake();

    Event::assertListening(
        PaymentFailed::class,
        NotifyUserOfPaymentFailure::class
    );
    Event::assertListening(PaymentFailed::class, MarkOrderAsFailed::class);
});
