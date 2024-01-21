<?php

namespace Modules\Payment;

class PayBuddyGateway implements PaymentGateway
{
    public function __construct(protected PayBuddySdk $payBuddySdk)
    {
    }

    public function charge(PaymentDetails $details): SuccessfulPayment
    {
        $charge = $this->payBuddySdk->charge(
            token: $details->token,
            amountInCents: $details->amountInCents,
            statmentDescription: $details->statementDescription
        );

        return new SuccessfulPayment(
            $charge["id"],
            $charge["amount_in_cents"],
            paymentProvider: $this->id()
        );
    }

    public function id(): PaymentProvider
    {
        return PaymentProvider::PayBuddy;
    }
}
