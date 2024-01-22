<?php

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Mail;
use Modules\Order\Checkout\OrderReceived;
use Modules\Order\Order;
use Modules\Payment\PayBuddySdk;
use Modules\Payment\Payment;
use Modules\Payment\PaymentProvider;
use Modules\Product\database\factories\ProductFactory;

it("succesfully creates an order", function () {
    Mail::fake();

    $user = UserFactory::new()->create();

    $products = ProductFactory::new()
        ->count(2)
        ->create(
            new Sequence(
                [
                    "name" => "Very expensive air fryer",
                    "price_in_cents" => 10000,
                    "stock" => 10,
                ],
                [
                    "name" => "Macbook Pro 5",
                    "price_in_cents" => 50000,
                    "stock" => 10,
                ]
            )
        );

    $paymentToken = PayBuddySdk::validToken();

    $response = $this->actingAs($user)->post(route("order::checkout"), [
        "payment_token" => $paymentToken,
        "products" => [
            ["id" => $products->first()->id, "quantity" => 1],
            ["id" => $products->last()->id, "quantity" => 1],
        ],
    ]);

    $order = Order::query()
        ->latest("id")
        ->first();

    $response->assertStatus(Response::HTTP_CREATED)->assertJson([
        "order_url" => $order->url(),
    ]);

    Mail::assertSent(OrderReceived::class, function (OrderReceived $mail) use (
        $user
    ) {
        return $mail->hasTo($user->email);
    });

    // order
    $this->assertTrue($order->user->is($user));
    $this->assertEquals(60000, $order->total_in_cents);
    $this->assertEquals("completed", $order->status);

    // payment
    /** @var Payment $payment */
    $payment = $order->lastPayment;
    $this->assertEquals("paid", $payment->status);
    $this->assertEquals(PaymentProvider::PayBuddy, $payment->payment_gateway);
    $this->assertEquals(36, strlen($payment->payment_id));
    $this->assertEquals(60000, $payment->total_in_cents);
    $this->assertTrue($payment->user->is($user));

    foreach ($products as $product) {
        /** @var \Modules\Order\OrderLine $orderLine */
        $orderLine = $order->lines->where("product_id", $product->id)->first();

        $this->assertEquals(
            $product->price_in_cents,
            $orderLine->product_price_in_cents
        );
        $this->assertEquals(1, $orderLine->quantity);
    }

    $products = $products->fresh();

    $this->assertEquals(9, $products->first()->stock);
    $this->assertEquals(9, $products->last()->stock);
});

it("fails with an invalid token", function () {
    $user = UserFactory::new()->create();
    $product = ProductFactory::new()->create();
    $paymentToken = PayBuddySdk::invalidToken();

    $response = $this->actingAs($user)->postJson(route("order::checkout"), [
        "payment_token" => $paymentToken,
        "products" => [["id" => $product->id, "quantity" => 1]],
    ]);

    $response
        ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
        ->assertJsonValidationErrors(["payment_token"]);

    $this->assertEquals(0, $user->orders()->count());
    $this->assertEquals(0, Order::query()->count());
});
