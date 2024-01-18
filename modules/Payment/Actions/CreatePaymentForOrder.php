<?php

namespace Modules\Payment\Actions;

use Illuminate\Validation\ValidationException;
use Modules\Order\Exceptions\PaymentFailedException;
use Modules\Payment\Payment;
use Modules\Payment\PayBuddy;
use RuntimeException;

class CreatePaymentForOrder
{
    /**
     * @throws \Modules\Order\Exceptions\PaymentFailedException
     */
    public function handle(
        int $orderId,
        int $userId,
        int $totalInCents,
        PayBuddy $payBuddy,
        string $paymentToken
    ): Payment {
        try {
            $charge = $payBuddy->charge(
                $paymentToken,
                $totalInCents,
                "Modularization"
            );
        } catch (RuntimeException) {
            throw PaymentFailedException::dueToInvalidToken();
        }

        return Payment::query()->create([
            "total_in_cents" => $totalInCents,
            "status" => "paid",
            "payment_gateway" => "PayBuddy",
            "payment_id" => $charge["id"],
            "user_id" => $userId,
            "order_id" => $orderId,
        ]);
    }
}
