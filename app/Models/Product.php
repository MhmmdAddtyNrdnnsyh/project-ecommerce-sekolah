<?php

namespace App\Models;

use App\Enums\ProductFulfillmentType;
use App\Enums\ProductSalesMethod;
use App\Enums\ProductStatus;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

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
 * @property ProductFulfillmentType $fulfillment_type
 * @property int|null $pre_order_estimate_days
 * @property Carbon|null $pre_order_deadline
 * @property int|null $pre_order_min_quantity
 * @property string|null $pre_order_note
 * @property ProductStatus $status
 * @property string|null $rejection_reason
 * @property string|null $image
 * @property User|null $seller
 * @property UpJurusan|null $upJurusan
 * @property Category $category
 */
#[Fillable(['seller_id', 'up_jurusan_id', 'category_id', 'name', 'slug', 'description', 'price', 'stock', 'sales_method', 'fulfillment_type', 'pre_order_estimate_days', 'pre_order_deadline', 'pre_order_min_quantity', 'pre_order_note', 'status', 'rejection_reason', 'image'])]
class Product extends Model
{
    public const int LOW_STOCK_THRESHOLD = 5;

    public const string REAL_STOCK_SQL = "(CASE WHEN products.sales_method = 'up_jurusan' AND products.seller_id IS NOT NULL THEN (SELECT COALESCE(SUM(received_quantity - sold_quantity), 0) FROM up_jurusan_consignments WHERE up_jurusan_consignments.product_id = products.id) ELSE products.stock END)";

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
            'fulfillment_type' => ProductFulfillmentType::class,
            'pre_order_estimate_days' => 'integer',
            'pre_order_deadline' => 'date',
            'pre_order_min_quantity' => 'integer',
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

    public function isPreOrder(): bool
    {
        return $this->fulfillment_type === ProductFulfillmentType::PreOrder;
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

    public static function realStockSql(): string
    {
        return self::REAL_STOCK_SQL;
    }
}
