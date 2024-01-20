<?php

namespace Modules\Order\Events;

use Modules\Order\DTOs\OrderDto;
use Modules\User\UserDto;

class OrderFulfilled
{
    public function __construct(public OrderDto $order, public UserDto $user)
    {
    }
}
