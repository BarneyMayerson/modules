<?php

namespace Modules\Order;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Order\OrderMissingOrderLinesException;
use Modules\Payment\Payment;
use Modules\Product\CartItem;
use Modules\Product\CartItemCollection;
use NumberFormatter;

class Order extends Model
{
    use HasFactory;

    public const COMPLETED = "completed";
    public const PENDING = "pending";

    protected $guarded = [];

    protected $casts = [
        "user_id" => "integer",
        "total_in_cents" => "integer",
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(OrderLine::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function lastPayment(): HasOne
    {
        return $this->payments()
            ->one()
            ->latest();
    }

    public function url(): string
    {
        return route("order.show", $this);
    }

    public function localizedTotal(): string
    {
        $numberFormatter = new NumberFormatter(
            "en-US",
            NumberFormatter::CURRENCY
        );

        return $numberFormatter->format($this->total_in_cents / 100);
    }

    /**
     * Undocumented function
     *
     * @param CartItemCollection<CartItem> $items
     * @return void
     */
    public function addLinesFromCartItems(CartItemCollection $items): void
    {
        foreach ($items->items() as $item) {
            $this->lines->push(
                OrderLine::make([
                    "product_id" => $item->product->id,
                    "product_price_in_cents" => $item->product->priceInCents,
                    "quantity" => $item->quantity,
                ])
            );
        }

        $this->total_in_cents = $this->lines->sum(
            fn(OrderLine $line) => $line->product_price_in_cents
        );
    }

    /**
     * @throws \Modules\Order\Exceptions\OrderMissingOrderLinesException
     */
    public function fulfill(): void
    {
        if ($this->lines->isEmpty()) {
            throw new OrderMissingOrderLinesException();
        }

        $this->status = self::COMPLETED;

        $this->save();
        $this->lines()->saveMany($this->lines);
    }

    public static function startForUser(int $userId): self
    {
        return self::make([
            "user_id" => $userId,
            "status" => self::PENDING,
        ]);
    }
}
