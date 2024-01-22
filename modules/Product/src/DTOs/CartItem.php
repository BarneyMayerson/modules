<?php

namespace Modules\Product\DTOs;

class CartItem
{
    public function __construct(
        public ProductDto $product,
        public int $quantity
    ) {
    }
}
