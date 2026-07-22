<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string|null $code
 * @property int $user_id
 * @property OrderStatus $status
 * @property PaymentStatus $payment_status
 * @property PaymentMethod $payment_method
 * @property string|null $payment_proof_path
 * @property Carbon|null $payment_confirmed_at
 * @property int|null $payment_confirmed_by
 * @property string|null $payment_rejection_reason
 * @property int $total_price
 * @property string $pickup_method
 * @property string|null $pickup_location
 * @property Carbon|null $expires_at
 * @property Carbon|null $cancelled_at
 * @property int|null $cancelled_by
 * @property string|null $cancel_reason
 * @property bool $requires_manual_review
 * @property Carbon|null $requires_manual_review_at
 * @property string|null $requires_manual_review_reason
 * @property Carbon|null $stuck_detected_at
 * @property array<int, string>|null $stuck_reasons
 * @property User $user
 */
#[Fillable(['code', 'user_id', 'status', 'payment_status', 'payment_method', 'payment_proof_path', 'payment_confirmed_at', 'payment_confirmed_by', 'payment_rejection_reason', 'total_price', 'pickup_method', 'pickup_location', 'expires_at', 'cancelled_at', 'cancelled_by', 'cancel_reason', 'requires_manual_review', 'requires_manual_review_at', 'requires_manual_review_reason', 'stuck_detected_at', 'stuck_reasons'])]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payment_status' => PaymentStatus::class,
            'payment_method' => PaymentMethod::class,
            'payment_confirmed_at' => 'datetime',
            'expires_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'requires_manual_review' => 'boolean',
            'requires_manual_review_at' => 'datetime',
            'stuck_detected_at' => 'datetime',
            'stuck_reasons' => 'array',
            'total_price' => 'integer',
        ];
    }

    /**
     * @return Attribute<OrderStatus, OrderStatus|string>
     */
    protected function status(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value): OrderStatus => OrderStatus::fromStorage($value ?? OrderStatus::Open->value),
            set: fn (OrderStatus|string $value): string => $value instanceof OrderStatus
                ? $value->value
                : OrderStatus::fromStorage($value)->value,
        );
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<OrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
