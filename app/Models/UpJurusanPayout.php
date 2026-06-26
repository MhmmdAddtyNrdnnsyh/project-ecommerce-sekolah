<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $up_jurusan_consignment_id
 * @property int $seller_id
 * @property int $user_id
 * @property int $amount
 * @property string|null $note
 */
#[Fillable(['up_jurusan_consignment_id', 'seller_id', 'user_id', 'amount', 'note'])]
class UpJurusanPayout extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<UpJurusanConsignment, $this>
     */
    public function consignment(): BelongsTo
    {
        return $this->belongsTo(UpJurusanConsignment::class, 'up_jurusan_consignment_id');
    }
}
