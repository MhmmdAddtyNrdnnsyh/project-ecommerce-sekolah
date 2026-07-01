<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $up_jurusan_daily_report_id
 * @property int|null $up_jurusan_pos_sale_id
 * @property string $code
 * @property int $total_quantity
 * @property int $total_amount
 * @property int $commission_amount
 * @property int $seller_amount
 * @property Carbon|null $sold_at
 * @property Collection<int, UpJurusanDailyReportTransactionItem> $items
 */
#[Fillable(['up_jurusan_daily_report_id', 'up_jurusan_pos_sale_id', 'code', 'total_quantity', 'total_amount', 'commission_amount', 'seller_amount', 'sold_at'])]
class UpJurusanDailyReportTransaction extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'total_quantity' => 'integer',
            'total_amount' => 'integer',
            'commission_amount' => 'integer',
            'seller_amount' => 'integer',
            'sold_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<UpJurusanDailyReport, $this>
     */
    public function report(): BelongsTo
    {
        return $this->belongsTo(UpJurusanDailyReport::class, 'up_jurusan_daily_report_id');
    }

    /**
     * @return BelongsTo<UpJurusanPosSale, $this>
     */
    public function posSale(): BelongsTo
    {
        return $this->belongsTo(UpJurusanPosSale::class, 'up_jurusan_pos_sale_id');
    }

    /**
     * @return HasMany<UpJurusanDailyReportTransactionItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(UpJurusanDailyReportTransactionItem::class, 'up_jurusan_daily_report_transaction_id');
    }
}
