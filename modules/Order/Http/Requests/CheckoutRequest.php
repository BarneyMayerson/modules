<?php

namespace Modules\Order\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
{
    public function autorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            "payment_token" => "required|string",
            "products" => "required|array",
            "products.*.id" => "required|numeric",
            "products.*.quantity" => "required|numeric",
        ];
    }
}
