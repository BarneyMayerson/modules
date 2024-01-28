<?php

use Modules\Order\Checkout\OrderReceived;
use Modules\Order\Contracts\OrderDto;

it("renders the mailable", function () {
    $orderDto = new OrderDto(
        id: 1,
        totalInCents: 99_00,
        localizedTotal: '$99.00',
        url: route("order.show", 1),
        lines: []
    );

    $orderReceived = new OrderReceived($orderDto);

    $this->assertIsString($orderReceived->render());
    $this->assertIsString($orderReceived->render());
});
