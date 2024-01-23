<?php

use App\Models\User;
use Mockery\MockInterface;
use Modules\Order\Checkout\OrderFulfilled;
use Modules\Order\Checkout\PurchaseItems;
use Modules\Order\Contracts\PendingPayment;
use Modules\Order\Order;
use Modules\Payment\Actions\CreatePaymentForOrderInterface;
use Modules\Payment\Exceptions\PaymentFailedException;
use Modules\Payment\InMemoryGateway;
use Modules\Payment\PayBuddySdk;
use Modules\Payment\Payment;
use Modules\Product\Collections\CartItemCollection;
use Modules\Product\DTOs\ProductDto;
use Modules\Product\Models\Product;
use Modules\User\UserDto;

it("creates an order", function () {
    Mail::fake();
    Event::fake();

    $user = User::factory()->create();
    $product = Product::factory()->create([
        "stock" => 10,
        "price_in_cents" => 1200,
    ]);
    $cartItemCollection = CartItemCollection::fromProduct(
        product: ProductDto::fromEloquentModel($product),
        quantity: 2
    );
    $pendingPayment = new PendingPayment(
        provider: new InMemoryGateway(),
        paymentToken: PayBuddySdk::validToken()
    );
    $userDto = UserDto::fromEloquentModel($user);

    /** @var PurchaseItems $purchaseItems */
    $purchaseItems = app(abstract: PurchaseItems::class);
    $order = $purchaseItems->handle(
        items: $cartItemCollection,
        pendingPayment: $pendingPayment,
        user: $userDto
    );
    $orderLine = $order->lines[0];

    $this->assertEquals($product->price_in_cents * 2, $order->totalInCents);
    $this->assertCount(1, $order->lines);
    $this->assertEquals($product->id, $orderLine->productId);
    $this->assertEquals(
        $product->price_in_cents,
        $orderLine->productPriceInCents
    );
    $this->assertEquals(2, $orderLine->quantity);

    Event::assertDispatched(OrderFulfilled::class, function (
        OrderFulfilled $event
    ) use ($userDto, $order) {
        return $event->order === $order && $event->user === $userDto;
    });
});

it("does not create an order if something fails", function () {
    Mail::fake();
    Event::fake();

    $this->expectException(PaymentFailedException::class);

    $this->mock(
        abstract: CreatePaymentForOrderInterface::class,
        mock: function (MockInterface $mock) {
            $mock->allows("handle")->andThrow(new PaymentFailedException());
        }
    );

    $user = User::factory()->create();
    $product = Product::factory()->create();

    $cartItemCollection = CartItemCollection::fromProduct(
        product: ProductDto::fromEloquentModel($product)
    );
    $pendingPayment = new PendingPayment(
        provider: new InMemoryGateway(),
        paymentToken: PayBuddySdk::validToken()
    );
    $userDto = UserDto::fromEloquentModel($user);

    /** @var PurchaseItems $purchaseItems */
    $purchaseItems = app(abstract: PurchaseItems::class);

    try {
        $purchaseItems->handle(
            items: $cartItemCollection,
            pendingPayment: $pendingPayment,
            user: $userDto
        );
    } finally {
        $this->assertEquals(0, Order::count());
        $this->assertEquals(0, Payment::count());
        Event::assertNotDispatched(OrderFulfilled::class);
    }
});
