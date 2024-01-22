<?php

namespace Modules\Order\Infrastructure\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as BaseServiceProvider;
use Modules\Order\Checkout\OrderFulfilled;
use Modules\Order\Checkout\SendOrderConfirmationEmal;

class EventServiceProvider extends BaseServiceProvider
{
    protected $listen = [
        OrderFulfilled::class => [SendOrderConfirmationEmal::class],
    ];
}
