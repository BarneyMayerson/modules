<?php

namespace Modules\Payment;

use Modules\Order\Contracts\OrderDto;
use Modules\User\UserDto;

class PaymentFailed
{
    public function __construct(
        public OrderDto $order,
        public UserDto $user,
        public string $reason
    ) {
    }
}
