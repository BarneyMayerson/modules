<?php

namespace Modules\Order\Actions;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\DatabaseManager;
use Modules\Order\DTOs\PendingPayment;
use Modules\Order\Events\OrderFulfilled;
use Modules\Order\Models\Order;
use Modules\Payment\Actions\CreatePaymentForOrder;
use Modules\Product\CartItemCollection;
use Modules\Product\Warehouse\ProductStockManager;
use Modules\User\UserDto;

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
        PendingPayment $pendingPayment,
        UserDto $user
    ): Order {
        $order = $this->databaseManager->transaction(function () use (
            $pendingPayment,
            $user,
            $items
        ) {
            $order = Order::startForUser($user->id);
            $order->addLinesFromCartItems($items);
            $order->fulfill();

            $this->createPaymentForOrder->handle(
                orderId: $order->id,
                userId: $user->id,
                totalInCents: $items->totalInCents(),
                payBuddy: $pendingPayment->provider,
                paymentToken: $pendingPayment->paymentToken
            );

            return $order;
        });

        $this->events->dispatch(
            new OrderFulfilled(
                orderId: $order->id,
                totalInCents: $order->total_in_cents,
                localizedTotal: $order->localizedTotal(),
                cartItems: $items,
                userId: $user->id,
                userEmail: $user->email
            )
        );

        return $order;
    }
}
