<?php

namespace Modules\Product\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as BaseServiceProvider;
use Modules\Payment\PaymentSucceeded;
use Modules\Product\Events\DecreaseProductStock;

class EventServiceProvider extends BaseServiceProvider
{
    protected $listen = [
        PaymentSucceeded::class => [DecreaseProductStock::class],
    ];
}
