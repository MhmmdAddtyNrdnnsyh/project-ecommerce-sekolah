<?php

namespace App\Support;

use App\Enums\ProductStatus;
use App\Models\Product;
use Carbon\CarbonInterface;
use Illuminate\Validation\ValidationException;

class PurchasableProductService
{
    public const REASON_PRODUCT_REJECTED = 'product_rejected';

    public const REASON_PRODUCT_DELETED = 'product_deleted';

    public const REASON_OUT_OF_STOCK = 'out_of_stock';

    public const REASON_PREORDER_DEADLINE_PASSED = 'preorder_deadline_passed';

    public const REASON_PREORDER_MIN_QUANTITY = 'preorder_min_quantity';

    public const REASON_OWNERSHIP_INVALID = 'ownership_invalid';

    /**
     * Machine-readable invalidation codes for cart/checkout hygiene.
     *
     * @return list<string>
     */
    public static function invalidReasonCodes(
        ?Product $product,
        int $quantity,
        ?CarbonInterface $now = null,
    ): array {
        if ($product === null) {
            return [self::REASON_PRODUCT_DELETED];
        }

        $reasons = [];

        if ($product->status !== ProductStatus::Approved) {
            $reasons[] = self::REASON_PRODUCT_REJECTED;
        }

        if ($product->seller_id === null && $product->up_jurusan_id === null) {
            $reasons[] = self::REASON_OWNERSHIP_INVALID;
        }

        if (! $product->isPreOrder() && $quantity > $product->availableStock()) {
            $reasons[] = self::REASON_OUT_OF_STOCK;
        }

        if (PreOrderRules::isDeadlinePassed($product, $now)) {
            $reasons[] = self::REASON_PREORDER_DEADLINE_PASSED;
        }

        if (PreOrderRules::isBelowMinimumQuantity($product, $quantity)) {
            $reasons[] = self::REASON_PREORDER_MIN_QUANTITY;
        }

        return $reasons;
    }

    /**
     * Human-readable messages for mutation errors (cart add/update, checkout).
     *
     * @return list<string>
     */
    public static function invalidReasonMessages(
        ?Product $product,
        int $quantity,
        ?CarbonInterface $now = null,
    ): array {
        $codes = self::invalidReasonCodes($product, $quantity, $now);
        $name = $product?->name ?? 'Produk';

        return array_map(
            fn (string $code) => self::messageForCode($code, $product, $name),
            $codes,
        );
    }

    public static function isValid(
        ?Product $product,
        int $quantity,
        ?CarbonInterface $now = null,
    ): bool {
        return self::invalidReasonCodes($product, $quantity, $now) === [];
    }

    public static function assertPurchasable(
        Product $product,
        int $quantity,
        string $attribute = 'quantity',
    ): void {
        $messages = self::invalidReasonMessages($product, $quantity);

        if ($messages === []) {
            return;
        }

        throw ValidationException::withMessages([
            $attribute => $messages[0],
        ]);
    }

    public static function assertPurchasableForCheckout(Product $product, int $quantity): void
    {
        $messages = self::invalidReasonMessages($product, $quantity);

        if ($messages === []) {
            return;
        }

        throw ValidationException::withMessages([
            'cart' => "{$product->name}: {$messages[0]}",
        ]);
    }

    public static function assertApproved(Product $product): void
    {
        if ($product->status === ProductStatus::Approved) {
            return;
        }

        throw ValidationException::withMessages([
            'cart' => "Produk {$product->name} tidak tersedia untuk checkout.",
        ]);
    }

    private static function messageForCode(string $code, ?Product $product, string $name): string
    {
        return match ($code) {
            self::REASON_PRODUCT_DELETED => 'Produk tidak ditemukan atau sudah dihapus.',
            self::REASON_PRODUCT_REJECTED => "Produk {$name} tidak tersedia (status moderasi).",
            self::REASON_OWNERSHIP_INVALID => "Produk {$name} tidak memiliki pemilik yang valid.",
            self::REASON_OUT_OF_STOCK => 'Quantity tidak boleh melebihi stok tersedia.',
            self::REASON_PREORDER_DEADLINE_PASSED => 'Batas waktu pre-order produk ini sudah lewat.',
            self::REASON_PREORDER_MIN_QUANTITY => 'Jumlah pre-order minimal '.($product?->pre_order_min_quantity ?? 0).' item.',
            default => "Produk {$name} tidak dapat dibeli.",
        };
    }
}
