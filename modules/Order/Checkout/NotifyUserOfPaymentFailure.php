<?php

namespace Modules\Order\Checkout;

use Illuminate\Support\Facades\Mail;
use Modules\Payment\PaymentFailed;

class NotifyUserOfPaymentFailure
{
    public function handle(PaymentFailed $event): void
    {
        Mail::to($event->user->email)->send(
            new PaymentForOrderFailed(
                order: $event->order,
                reason: $event->reason
            )
        );
    }
}
