<?php

namespace Modules\Product;

class CartItem
{
    public function __construct(public ProductDto $product, public int $quantity)
    {
    }
}