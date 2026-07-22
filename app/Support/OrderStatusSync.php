<?php

namespace App\Support;

use App\Models\Order;

/**
 * @deprecated Use OrderSettlementService::sync() directly. Kept as thin alias for E1–E5 call sites.
 */
class OrderStatusSync
{
    public static function sync(Order $order): void
    {
        OrderSettlementService::sync($order);
    }
}
