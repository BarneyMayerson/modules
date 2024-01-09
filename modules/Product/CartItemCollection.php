<?php

namespace Modules\Product;

use Illuminate\Support\Collection;
use Modules\Product\Models\Product;

class CartItemCollection
{
    /**
     * @param Collection<CartItem> $items
     */
    public function __construct(protected Collection $items)
    {
    }

    public static function fromCheckoutData($data): CartItemCollection
    {
        $items = collect($data)->map(function(array $productDetails) {
            return new CartItem(
                ProductDto::fromEloquentModel(Product::find($productDetails['id'])), $productDetails['quantity']);
        });

        return new self($items); 
    }

    public function totalInCents(): int
    {
        return $this->items->sum(fn (CartItem $item) => 
          $item->quantity * $item->product->priceInCents
        );
    }

    public function items(): Collection
    {
        return $this->items;
    }
}