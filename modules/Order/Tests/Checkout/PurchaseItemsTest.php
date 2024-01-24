<?php

use App\Models\User;
use Modules\Order\Checkout\OrderStarted;
use Modules\Order\Checkout\PurchaseItems;
use Modules\Order\CompleteOrder;
use Modules\Order\Contracts\PendingPayment;
use Modules\Payment\Actions\CreatePaymentForOrderInMemory;
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

    Event::assertDispatched(OrderStarted::class, function (
        OrderStarted $event
    ) use ($userDto, $order) {
        return $event->order === $order && $event->user === $userDto;
    });
});

it("does not create an order if something fails", function () {
    Mail::fake();
    Event::fake();

    $this->expectException(PaymentFailedException::class);

    $createPayment = new CreatePaymentForOrderInMemory();
    $createPayment->shouldFail();
    $this->app->instance(
        abstract: CreatePaymentForOrderInterface::class,
        instance: $createPayment
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
        // $this->assertEquals(0, Order::count());
        $this->assertEquals(0, Payment::count());
        $this->assertCount(0, $createPayment->payments);
        Event::assertNotDispatched(CompleteOrder::class);
    }
});
