<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $up_jurusan_consignment_id
 * @property int $user_id
 * @property string $type
 * @property int $quantity
 * @property int $unit_price
 * @property int $gross_amount
 * @property int $commission_amount
 * @property int $seller_amount
 * @property string|null $note
 * @property UpJurusanConsignment $consignment
 * @property User $user
 */
#[Fillable(['up_jurusan_consignment_id', 'user_id', 'type', 'quantity', 'unit_price', 'gross_amount', 'commission_amount', 'seller_amount', 'note'])]
class UpJurusanStockMovement extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
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
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
