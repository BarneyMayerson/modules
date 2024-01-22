<?php
namespace Modules\Order;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderLine extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        "order_id" => "integer",
        "product_id" => "integer",
        "product_price_in_cents" => "integer",
        "quantity" => "integer",
    ];
}
