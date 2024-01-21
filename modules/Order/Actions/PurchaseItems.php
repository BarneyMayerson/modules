<?php

namespace Modules\Order\Actions;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\DatabaseManager;
use Modules\Order\DTOs\OrderDto;
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
    ): OrderDto {
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
                paymentGateway: $pendingPayment->provider,
                paymentToken: $pendingPayment->paymentToken
            );

            return OrderDto::fromEloquentModel($order);
        });

        $this->events->dispatch(new OrderFulfilled(order: $order, user: $user));

        return $order;
    }
}
