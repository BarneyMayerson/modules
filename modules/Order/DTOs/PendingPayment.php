<?php

namespace Modules\Order\DTOs;

use Modules\Payment\PaymentGateway;

class PendingPayment
{
    public function __construct(
        public PaymentGateway $provider,
        public string $paymentToken
    ) {
    }
}
