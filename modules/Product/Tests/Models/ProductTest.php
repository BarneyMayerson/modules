<?php

use Modules\Product\Models\Product;

it('creates a product', function() {
    /** @var Product $product */
    $product = Product::factory()->create();
    
    $this->assertTrue(true);
});