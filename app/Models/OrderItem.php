<?php

namespace App\Models;

use App\Enums\OrderItemStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Database\Factories\OrderItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $order_id
 * @property int $product_id
 * @property string $product_name
 * @property int $price
 * @property int $quantity
 * @property int $subtotal
 * @property OrderItemStatus $status
 * @property PaymentStatus $payment_status
 * @property PaymentMethod $payment_method
 * @property Carbon|null $payment_confirmed_at
 * @property int|null $payment_confirmed_by
 * @property string|null $payment_rejection_reason
 * @property bool $is_pre_order
 * @property int|null $pre_order_estimate_days
 * @property Carbon|null $pre_order_deadline
 * @property int|null $pre_order_min_quantity
 * @property string|null $pre_order_note
 * @property Order $order
 * @property Product $product
 */
#[Fillable(['order_id', 'product_id', 'product_name', 'price', 'quantity', 'subtotal', 'status', 'payment_status', 'payment_method', 'payment_confirmed_at', 'payment_confirmed_by', 'payment_rejection_reason', 'is_pre_order', 'pre_order_estimate_days', 'pre_order_deadline', 'pre_order_min_quantity', 'pre_order_note'])]
class OrderItem extends Model
{
    /** @use HasFactory<OrderItemFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'quantity' => 'integer',
            'subtotal' => 'integer',
            'status' => OrderItemStatus::class,
            'payment_status' => PaymentStatus::class,
            'payment_method' => PaymentMethod::class,
            'payment_confirmed_at' => 'datetime',
            'is_pre_order' => 'boolean',
            'pre_order_estimate_days' => 'integer',
            'pre_order_deadline' => 'date',
            'pre_order_min_quantity' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
