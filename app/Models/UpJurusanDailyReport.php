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
 * @property int $up_jurusan_id
 * @property int $user_id
 * @property Carbon $report_date
 * @property int $total_sold
 * @property int $total_revenue
 * @property Carbon $submitted_at
 * @property Collection<int, UpJurusanDailyReportTransaction> $transactions
 */
#[Fillable(['up_jurusan_id', 'user_id', 'report_date', 'total_sold', 'total_revenue', 'submitted_at'])]
class UpJurusanDailyReport extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'report_date' => 'date:Y-m-d',
            'total_sold' => 'integer',
            'total_revenue' => 'integer',
            'submitted_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<UpJurusan, $this>
     */
    public function upJurusan(): BelongsTo
    {
        return $this->belongsTo(UpJurusan::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<UpJurusanDailyReportTransaction, $this>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(UpJurusanDailyReportTransaction::class, 'up_jurusan_daily_report_id');
    }
}
