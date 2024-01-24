<?php

namespace Modules\Order\Checkout;

use Modules\Order\Order;
use Modules\Payment\PaymentFailed;

class MarkOrderAsFailed
{
    public function handle(PaymentFailed $event): void
    {
        Order::find($event->order->id)->markAsFailed();
    }
}
