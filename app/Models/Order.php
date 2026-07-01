<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
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
 * @property User $user
 */
#[Fillable(['code', 'user_id', 'status', 'payment_status', 'payment_method', 'payment_proof_path', 'payment_confirmed_at', 'payment_confirmed_by', 'payment_rejection_reason', 'total_price', 'pickup_method', 'pickup_location'])]
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
            'status' => OrderStatus::class,
            'payment_status' => PaymentStatus::class,
            'payment_method' => PaymentMethod::class,
            'payment_confirmed_at' => 'datetime',
            'total_price' => 'integer',
        ];
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
