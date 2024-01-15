<?php

namespace Modules\Order\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Modules\Order\Actions\PurchaseItems;
use Modules\Order\Http\Requests\CheckoutRequest;
use Modules\Order\Models\Order;
use Modules\Payment\PayBuddy;
use Modules\Product\CartItemCollection;
use Modules\Product\Warehouse\ProductStockManager;

class CheckoutController
{
    public function __construct(protected PurchaseItems $purchaseItems)
    {        
    }

    public function __invoke(CheckoutRequest $request)
    {
        $cartItems = CartItemCollection::fromCheckoutData($request->input('products'));

        $this->purchaseItems->handle(
            $cartItems, 
            PayBuddy::make(), 
            $request->input('payment_token'),
            $request->user()->id
        );

        
        return response()->json([], Response::HTTP_CREATED);
    }
}