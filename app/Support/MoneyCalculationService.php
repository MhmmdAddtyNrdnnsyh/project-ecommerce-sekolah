<?php

namespace App\Support;

use App\Models\UpJurusanPayout;
use App\Models\UpJurusanStockMovement;

class MoneyCalculationService
{
    public static function grossAmount(int $unitPrice, int $quantity): int
    {
        return $unitPrice * $quantity;
    }

    public static function commissionAmount(int $grossAmount, int $commissionRate): int
    {
        return intdiv($grossAmount * $commissionRate, 100);
    }

    public static function sellerAmount(int $grossAmount, int $commissionAmount): int
    {
        return $grossAmount - $commissionAmount;
    }

    /**
     * Seller consignment sale split (POS / checkout).
     *
     * @return array{unit_price: int, gross_amount: int, commission_amount: int, seller_amount: int}
     */
    public static function consignmentSaleSplit(int $unitPrice, int $quantity, int $commissionRate): array
    {
        $grossAmount = self::grossAmount($unitPrice, $quantity);
        $commissionAmount = self::commissionAmount($grossAmount, $commissionRate);

        return [
            'unit_price' => $unitPrice,
            'gross_amount' => $grossAmount,
            'commission_amount' => $commissionAmount,
            'seller_amount' => self::sellerAmount($grossAmount, $commissionAmount),
        ];
    }

    /**
     * UP-owned product sale: 100% revenue to UP (commission = gross, seller = 0).
     *
     * @return array{unit_price: int, gross_amount: int, commission_amount: int, seller_amount: int}
     */
    public static function upOwnedProductSaleSplit(int $unitPrice, int $quantity): array
    {
        $grossAmount = self::grossAmount($unitPrice, $quantity);

        return [
            'unit_price' => $unitPrice,
            'gross_amount' => $grossAmount,
            'commission_amount' => $grossAmount,
            'seller_amount' => 0,
        ];
    }

    /**
     * Reverse / restock amounts proportional to an original out movement.
     * Full reverse copies stored amounts to avoid 1-IDR drift.
     *
     * @return array{unit_price: int, gross_amount: int, commission_amount: int, seller_amount: int}
     */
    public static function reverseMovementSplit(UpJurusanStockMovement $movement, int $restoreQty): array
    {
        if ($restoreQty <= 0 || $movement->quantity <= 0) {
            return [
                'unit_price' => (int) $movement->unit_price,
                'gross_amount' => 0,
                'commission_amount' => 0,
                'seller_amount' => 0,
            ];
        }

        if ($restoreQty === (int) $movement->quantity) {
            return [
                'unit_price' => (int) $movement->unit_price,
                'gross_amount' => (int) $movement->gross_amount,
                'commission_amount' => (int) $movement->commission_amount,
                'seller_amount' => (int) $movement->seller_amount,
            ];
        }

        $grossAmount = self::grossAmount((int) $movement->unit_price, $restoreQty);
        $commissionAmount = intdiv((int) $movement->commission_amount * $restoreQty, (int) $movement->quantity);

        return [
            'unit_price' => (int) $movement->unit_price,
            'gross_amount' => $grossAmount,
            'commission_amount' => $commissionAmount,
            'seller_amount' => self::sellerAmount($grossAmount, $commissionAmount),
        ];
    }

    /**
     * Payout balance: sum stored seller_amount on out movements only (no recompute).
     */
    public static function sellerEarningsFromOutMovements(int $consignmentId): int
    {
        return (int) UpJurusanStockMovement::query()
            ->where('up_jurusan_consignment_id', $consignmentId)
            ->where('type', 'out')
            ->sum('seller_amount');
    }

    public static function paidPayoutAmount(int $consignmentId): int
    {
        return (int) UpJurusanPayout::query()
            ->where('up_jurusan_consignment_id', $consignmentId)
            ->sum('amount');
    }

    public static function unpaidSellerAmount(int $consignmentId): int
    {
        return max(0, self::sellerEarningsFromOutMovements($consignmentId) - self::paidPayoutAmount($consignmentId));
    }
}
