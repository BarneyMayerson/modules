<?php

namespace Modules\Order\Infrastructure\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as BaseServiceProvider;
use Modules\Order\Checkout\MarkOrderAsFailed;
use Modules\Order\Checkout\NotifyUserOfPaymentFailure;
use Modules\Order\Checkout\OrderStarted;
use Modules\Order\Checkout\SendOrderConfirmationEmail;
use Modules\Order\CompleteOrder;
use Modules\Payment\PaymentFailed;
use Modules\Payment\PaymentSucceeded;
use Modules\Payment\PayOrder;

class EventServiceProvider extends BaseServiceProvider
{
    protected $listen = [
        OrderStarted::class => [
            SendOrderConfirmationEmail::class,
            PayOrder::class,
        ],
        PaymentSucceeded::class => [CompleteOrder::class],
        PaymentFailed::class => [
            MarkOrderAsFailed::class,
            NotifyUserOfPaymentFailure::class,
        ],
    ];
}
