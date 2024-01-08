<?php

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Http\Response;
use Modules\Order\Models\Order;
use Modules\Payment\PayBuddy;
use Modules\Product\Database\Factories\ProductFactory;

use function Pest\Laravel\actingAs;

it('succesfully creates an order', function() {
    $this->assertTrue(true);

    $user = UserFactory::new()->create();

    $products = ProductFactory::new()->count(2)->create(
        new Sequence(
            ['name' => 'Very expensive air fryer', 'price_in_cents' => 10000, 'stock' => 10],
            ['name' => 'Macbook Pro 5', 'price_in_cents' => 50000, 'stock' => 10],
        )
    );

    $paymentToken = PayBuddy::validToken();

    $response = $this->actingAs($user)
        ->post(route('order::checkout'), [
            'payment_token' =>$paymentToken,
            'products' => [
                ['id' => $products->first()->id, 'quantity' => 1],
                ['id' => $products->last()->id, 'quantity' => 1],
            ],
        ]);

    $response->assertStatus(Response::HTTP_CREATED);

    $order = Order::query()->latest('id')->first();

    $this->assertTrue($order->user->is($user));
    $this->assertEquals(60000, $order->total_in_cents);
    $this->assertEquals('paid', $order->status);
    $this->assertEquals('PayBuddy', $order->payment_gateway);
    $this->assertEquals(36, strlen($order->payment_id));
    $this->assertCount(2, $order->lines);

    foreach ($products as $product) {
        /** @var \Modules\Order\Models\OrderLine $orderLine */
        $orderLine = $order->lines->where('product_id', $product->id)->first();

        $this->assertEquals($product->price_in_cents, $orderLine->product_price_in_cents);
        $this->assertEquals(1, $orderLine->quantity);
    }
});

it('fails with an invalid token', function() {
    $user = UserFactory::new()->create();
    $product = ProductFactory::new()->create();
    $paymentToken = PayBuddy::invalidToken();

    $response = $this->actingAs($user)
        ->postJson(route('order::checkout'), [
            'payment_token' => $paymentToken,
            'products' => [
                ['id' => $product->id, 'quantity' => 1],
            ],
        ]);

    $response
        ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
        ->assertJsonValidationErrors(['payment_token']);
});