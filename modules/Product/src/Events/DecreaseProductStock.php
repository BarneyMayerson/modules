<?php

namespace Modules\Product\Events;

use Modules\Payment\PaymentSucceeded;
use Modules\Product\Warehouse\ProductStockManager;

class DecreaseProductStock
{
    public function __construct(
        protected ProductStockManager $productStockManager
    ) {
    }

    public function handle(PaymentSucceeded $event): void
    {
        foreach ($event->order->lines as $orderLine) {
            $this->productStockManager->decrement(
                $orderLine->productId,
                $orderLine->quantity
            );
        }
    }
}
