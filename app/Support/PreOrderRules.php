<?php

namespace App\Support;

use App\Models\Product;
use Carbon\CarbonInterface;
use Illuminate\Validation\ValidationException;

class PreOrderRules
{
    public static function isDeadlinePassed(Product $product, ?CarbonInterface $now = null): bool
    {
        if (! $product->isPreOrder() || $product->pre_order_deadline === null) {
            return false;
        }

        $now ??= now();

        return $now->startOfDay()->greaterThan($product->pre_order_deadline->copy()->startOfDay());
    }

    public static function isBelowMinimumQuantity(Product $product, int $quantity): bool
    {
        if (! $product->isPreOrder() || $product->pre_order_min_quantity === null) {
            return false;
        }

        return $quantity < $product->pre_order_min_quantity;
    }

    /**
     * @return list<string>
     */
    public static function invalidReasons(Product $product, int $quantity, ?CarbonInterface $now = null): array
    {
        if (! $product->isPreOrder()) {
            return [];
        }

        $reasons = [];

        if (self::isDeadlinePassed($product, $now)) {
            $reasons[] = 'Batas waktu pre-order produk ini sudah lewat.';
        }

        if (self::isBelowMinimumQuantity($product, $quantity)) {
            $reasons[] = 'Jumlah pre-order minimal '.$product->pre_order_min_quantity.' item.';
        }

        return $reasons;
    }

    public static function isValid(Product $product, int $quantity, ?CarbonInterface $now = null): bool
    {
        return self::invalidReasons($product, $quantity, $now) === [];
    }

    public static function assertPurchasable(Product $product, int $quantity, string $attribute = 'quantity'): void
    {
        $reasons = self::invalidReasons($product, $quantity);

        if ($reasons === []) {
            return;
        }

        throw ValidationException::withMessages([
            $attribute => $reasons[0],
        ]);
    }

    public static function assertPurchasableForCheckout(Product $product, int $quantity): void
    {
        $reasons = self::invalidReasons($product, $quantity);

        if ($reasons === []) {
            return;
        }

        throw ValidationException::withMessages([
            'cart' => "{$product->name}: {$reasons[0]}",
        ]);
    }
}
