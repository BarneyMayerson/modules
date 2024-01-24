<?php

use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Modules\Order\Checkout\NotifyUserOfPaymentFailure;
use Modules\Order\Checkout\PaymentForOrderFailed;
use Modules\Order\Contracts\OrderDto;
use Modules\Order\Order;
use Modules\Payment\PaymentFailed;
use Modules\User\UserDto;

it("notifies the user of the payment failure", function () {
    Mail::fake();

    // $order = Order::factory()->create();
    $user = User::factory()->create();
    $order = Order::create([
        "user_id" => $user->id,
        "status" => Order::STARTED,
        "total_in_cents" => 199,
    ]);
    $orderDto = OrderDto::fromEloquentModel($order);
    $userDto = UserDto::fromEloquentModel($user);

    $event = new PaymentFailed(
        order: $orderDto,
        user: $userDto,
        reason: "Payment failed."
    );

    app(NotifyUserOfPaymentFailure::class)->handle($event);

    Mail::assertSent(
        PaymentForOrderFailed::class,
        fn(PaymentForOrderFailed $mailable) => $mailable->hasTo($userDto->email)
    );
});
