<?php

namespace Modules\Order\Checkout;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Modules\Order\Contracts\PendingPayment;
use Modules\Payment\Exceptions\PaymentFailedException;
use Modules\Payment\PaymentGateway;
use Modules\Product\Collections\CartItemCollection;
use Modules\User\UserDto;

class CheckoutController
{
    public function __construct(
        protected PurchaseItems $purchaseItems,
        protected PaymentGateway $paymentGateway
    ) {
    }

    public function __invoke(CheckoutRequest $request): JsonResponse
    {
        $cartItems = CartItemCollection::fromCheckoutData(
            $request->input("products")
        );
        $pendingPayment = new PendingPayment(
            provider: $this->paymentGateway,
            paymentToken: $request->input("payment_token")
        );
        $userDto = UserDto::fromEloquentModel($request->user());

        try {
            $order = $this->purchaseItems->handle(
                items: $cartItems,
                pendingPayment: $pendingPayment,
                user: $userDto
            );
        } catch (PaymentFailedException) {
            throw ValidationException::withMessages([
                "payment_token" => "We could not complete your payment.",
            ]);
        }

        return response()->json(
            [
                "order_url" => $order->url,
            ],
            Response::HTTP_CREATED
        );
    }
}
