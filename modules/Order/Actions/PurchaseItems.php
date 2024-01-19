<?php

namespace Modules\Order\Actions;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\DatabaseManager;
use Modules\Order\Events\OrderFulfilled;
use Modules\Order\Models\Order;
use Modules\Payment\Actions\CreatePaymentForOrder;
use Modules\Payment\PayBuddy;
use Modules\Product\CartItemCollection;
use Modules\Product\Warehouse\ProductStockManager;

class PurchaseItems
{
    public function __construct(
        protected ProductStockManager $productStockManager,
        protected CreatePaymentForOrder $createPaymentForOrder,
        protected DatabaseManager $databaseManager,
        protected Dispatcher $events
    ) {
    }

    public function handle(
        CartItemCollection $items,
        PayBuddy $paymentProvider,
        string $paymentToken,
        int $userId,
        string $userEmail
    ): Order {
        $order = $this->databaseManager->transaction(function () use (
            $userId,
            $items,
            $paymentProvider,
            $paymentToken
        ) {
            $order = Order::startForUser($userId);
            $order->addLinesFromCartItems($items);
            $order->fulfill();

            foreach ($items->items() as $cartItem) {
                $this->productStockManager->decrement(
                    $cartItem->product->id,
                    $cartItem->quantity
                );
            }

            $this->createPaymentForOrder->handle(
                $order->id,
                $userId,
                $items->totalInCents(),
                $paymentProvider,
                $paymentToken
            );

            return $order;
        });

        $this->events->dispatch(
            new OrderFulfilled(
                orderId: $order->id,
                totalInCents: $order->total_in_cents,
                localizedTotal: $order->localizedTotal(),
                userId: $userId,
                userEmail: $userEmail
            )
        );

        return $order;
    }
}
