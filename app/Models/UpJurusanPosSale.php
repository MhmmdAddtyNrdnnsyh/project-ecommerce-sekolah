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
 * @property string $code
 * @property int $total_quantity
 * @property int $total_amount
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Collection<int, UpJurusanStockMovement> $movements
 */
#[Fillable(['up_jurusan_id', 'user_id', 'code', 'total_quantity', 'total_amount'])]
class UpJurusanPosSale extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'total_quantity' => 'integer',
            'total_amount' => 'integer',
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
     * @return HasMany<UpJurusanStockMovement, $this>
     */
    public function movements(): HasMany
    {
        return $this->hasMany(UpJurusanStockMovement::class);
    }
}
