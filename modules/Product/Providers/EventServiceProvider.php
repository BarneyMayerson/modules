<?php

namespace Modules\Product\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as BaseServiceProvider;
use Modules\Order\Events\OrderFulfilled;
use Modules\Product\Events\DecreaseProductStock;

class EventServiceProvider extends BaseServiceProvider
{
    protected $listen = [
        OrderFulfilled::class => [DecreaseProductStock::class],
    ];
}
