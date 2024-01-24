<?php

namespace Modules\Payment;

use Illuminate\Contracts\Events\Dispatcher;
use Modules\Order\Checkout\OrderStarted;
use Modules\Payment\Actions\CreatePaymentForOrderInterface;
use Modules\Payment\Exceptions\PaymentFailedException;

class PayOrder
{
    public function __construct(
        protected CreatePaymentForOrderInterface $createPaymentForOrder,
        protected Dispatcher $events
    ) {
    }

    public function handle(OrderStarted $event): void
    {
        try {
            $this->createPaymentForOrder->handle(
                orderId: $event->order->id,
                userId: $event->user->id,
                totalInCents: $event->order->totalInCents,
                paymentGateway: $event->pendingPayment->provider,
                paymentToken: $event->pendingPayment->paymentToken
            );
        } catch (PaymentFailedException $exception) {
            $this->events->dispatch(
                new PaymentFailed(
                    order: $event->order,
                    user: $event->user,
                    reason: $exception->getMessage()
                )
            );

            throw $exception;
        }

        $this->events->dispatch(
            new PaymentSucceeded(order: $event->order, user: $event->user)
        );
    }
}
