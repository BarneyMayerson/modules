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

    public static function fromCheckoutData(array $data): CartItemCollection
    {
        $cartData = collect($data);
        $products = Product::whereIn('id', $cartData->pluck('id'))->get();

        $cartItems = $products->map(function(Product $productModel) use ($cartData) {
            $cartItem = $cartData->where('id', $productModel->id)->first();

            return new CartItem(
                ProductDto::fromEloquentModel($productModel),
                $cartItem['quantity']
            );
        });

        return new self($cartItems);
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