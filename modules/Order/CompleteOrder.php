<?php

namespace Modules\Order;

use Modules\Order\Order;
use Modules\Payment\PaymentSucceeded;

class CompleteOrder
{
    public function handle(PaymentSucceeded $event): void
    {
        Order::find($event->order->id)->complete();
    }
}
