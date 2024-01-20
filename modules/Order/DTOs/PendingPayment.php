<?php

namespace Modules\Order\DTOs;

use Modules\Payment\PayBuddy;

class PendingPayment
{
    public function __construct(
        public PayBuddy $provider,
        public string $paymentToken
    ) {
    }
}
