<?php

namespace App\Models;

use App\Enums\ProductSalesMethod;
use App\Enums\ProductStatus;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int|null $seller_id
 * @property int|null $up_jurusan_id
 * @property int $category_id
 * @property string $name
 * @property string $slug
 * @property string $description
 * @property int $price
 * @property int $stock
 * @property ProductSalesMethod $sales_method
 * @property ProductStatus $status
 * @property string|null $rejection_reason
 * @property string|null $image
 * @property User|null $seller
 * @property UpJurusan|null $upJurusan
 * @property Category $category
 */
#[Fillable(['seller_id', 'up_jurusan_id', 'category_id', 'name', 'slug', 'description', 'price', 'stock', 'sales_method', 'status', 'rejection_reason', 'image'])]
class Product extends Model
{
    public const int LOW_STOCK_THRESHOLD = 5;

    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'stock' => 'integer',
            'sales_method' => ProductSalesMethod::class,
            'status' => ProductStatus::class,
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
     * @return BelongsTo<UpJurusan, $this>
     */
    public function upJurusan(): BelongsTo
    {
        return $this->belongsTo(UpJurusan::class);
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return HasMany<CartItem, $this>
     */
    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    /**
     * @return HasMany<OrderItem, $this>
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * @return HasMany<UpJurusanConsignment, $this>
     */
    public function upJurusanConsignments(): HasMany
    {
        return $this->hasMany(UpJurusanConsignment::class);
    }

    public function usesConsignmentStock(): bool
    {
        return $this->sales_method === ProductSalesMethod::UpJurusan && $this->seller_id !== null;
    }

    public function availableStock(): int
    {
        if (! $this->usesConsignmentStock()) {
            return $this->stock;
        }

        return (int) $this->upJurusanConsignments()
            ->selectRaw('COALESCE(SUM(received_quantity - sold_quantity), 0) as available')
            ->value('available');
    }
}
