<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $up_jurusan_daily_report_transaction_id
 * @property int|null $up_jurusan_stock_movement_id
 * @property string $product_name
 * @property string $source
 * @property int $quantity
 * @property int $unit_price
 * @property int $subtotal
 */
#[Fillable(['up_jurusan_daily_report_transaction_id', 'up_jurusan_stock_movement_id', 'product_name', 'source', 'quantity', 'unit_price', 'subtotal'])]
class UpJurusanDailyReportTransactionItem extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'integer',
            'subtotal' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<UpJurusanDailyReportTransaction, $this>
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(UpJurusanDailyReportTransaction::class, 'up_jurusan_daily_report_transaction_id');
    }

    /**
     * @return BelongsTo<UpJurusanStockMovement, $this>
     */
    public function stockMovement(): BelongsTo
    {
        return $this->belongsTo(UpJurusanStockMovement::class, 'up_jurusan_stock_movement_id');
    }
}
