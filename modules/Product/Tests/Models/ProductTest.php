<?php

use Modules\Product\Models\Product;

it('creates a product', function() {
    $product = Product::factory()->create();

    $this->assertTrue(true);
});