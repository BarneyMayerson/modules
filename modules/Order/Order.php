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
use Modules\Product\Collections\CartItemCollection;
use NumberFormatter;

class Order extends Model
{
    use HasFactory;

    public const COMPLETED = "completed";
    public const PAYMENT_FAILED = "payment failed";
    public const PENDING = "pending";
    public const STARTED = "started";

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
        return (new NumberFormatter(
            "en-US",
            NumberFormatter::CURRENCY
        ))->formatCurrency($this->total_in_cents / 100, "USD");
    }

    public function isCompleted(): bool
    {
        return $this->status === self::COMPLETED;
    }

    public function complete(): void
    {
        $this->status = self::COMPLETED;

        $this->save();
    }

    public function markAsFailed(): void
    {
        if ($this->isCompleted()) {
            throw new \RuntimeException(
                "A completed order cannot be marked as failed."
            );
        }

        $this->status = self::PAYMENT_FAILED;

        $this->save();
    }

    public function addLines(array $lines): void
    {
        foreach ($lines as $line) {
            $this->lines->push($line);
        }

        $this->total_in_cents = $this->lines->sum(
            fn(OrderLine $line) => $line->total()
        );
    }

    /**
     * @param CartItemCollection<CartItem> $items
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
            fn(OrderLine $line) => $line->product_price_in_cents *
                $line->quantity
        );
    }

    /**
     * @throws \Modules\Order\Exceptions\OrderMissingOrderLinesException
     */
    public function start(): void
    {
        if ($this->lines->isEmpty()) {
            throw new OrderMissingOrderLinesException();
        }

        $this->status = self::PENDING;

        $this->save();
        $this->lines()->saveMany($this->lines);
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
