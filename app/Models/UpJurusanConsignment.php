<?php

namespace App\Models;

use App\Enums\UpJurusanConsignmentStatus;
use Database\Factories\UpJurusanConsignmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $seller_id
 * @property int $product_id
 * @property int $up_jurusan_id
 * @property int $requested_quantity
 * @property int $received_quantity
 * @property int $sold_quantity
 * @property int $commission_rate
 * @property UpJurusanConsignmentStatus $status
 * @property string|null $note
 * @property User $seller
 * @property Product $product
 * @property UpJurusan $upJurusan
 */
#[Fillable(['seller_id', 'product_id', 'up_jurusan_id', 'requested_quantity', 'received_quantity', 'sold_quantity', 'commission_rate', 'status', 'note'])]
class UpJurusanConsignment extends Model
{
    /** @use HasFactory<UpJurusanConsignmentFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'requested_quantity' => 'integer',
            'received_quantity' => 'integer',
            'sold_quantity' => 'integer',
            'commission_rate' => 'integer',
            'status' => UpJurusanConsignmentStatus::class,
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<UpJurusan, $this>
     */
    public function upJurusan(): BelongsTo
    {
        return $this->belongsTo(UpJurusan::class);
    }
}
