<?php

namespace App\Models;

use App\Enums\StockMovementSource;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int|null $up_jurusan_consignment_id
 * @property int|null $product_id
 * @property int|null $up_jurusan_pos_sale_id
 * @property int|null $order_id
 * @property int $user_id
 * @property string $type
 * @property StockMovementSource|null $source
 * @property int $quantity
 * @property int $unit_price
 * @property int $gross_amount
 * @property int $commission_amount
 * @property int $seller_amount
 * @property string|null $note
 * @property int|null $reverses_movement_id
 * @property UpJurusanConsignment|null $consignment
 * @property Product|null $product
 * @property UpJurusanPosSale|null $posSale
 * @property User $user
 */
#[Fillable(['up_jurusan_consignment_id', 'product_id', 'up_jurusan_pos_sale_id', 'order_id', 'user_id', 'type', 'source', 'quantity', 'unit_price', 'gross_amount', 'commission_amount', 'seller_amount', 'note', 'reverses_movement_id'])]
class UpJurusanStockMovement extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'source' => StockMovementSource::class,
            'quantity' => 'integer',
            'unit_price' => 'integer',
            'gross_amount' => 'integer',
            'commission_amount' => 'integer',
            'seller_amount' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<UpJurusanConsignment, $this>
     */
    public function consignment(): BelongsTo
    {
        return $this->belongsTo(UpJurusanConsignment::class, 'up_jurusan_consignment_id');
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<UpJurusanPosSale, $this>
     */
    public function posSale(): BelongsTo
    {
        return $this->belongsTo(UpJurusanPosSale::class, 'up_jurusan_pos_sale_id');
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
