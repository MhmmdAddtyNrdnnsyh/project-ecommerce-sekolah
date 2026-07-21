<?php

namespace App\Support;

use App\Enums\OrderItemStatus;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;

class OrderStatusSync
{
    public static function sync(Order $order): void
    {
        $items = $order->items()
            ->select(['id', 'order_id', 'status'])
            ->get();

        if ($items->isEmpty()) {
            $order->update(['status' => OrderStatus::Pending]);

            return;
        }

        $allCompleted = $items->every(fn (OrderItem $item) => $item->status === OrderItemStatus::Completed);
        $allCancelled = $items->every(fn (OrderItem $item) => $item->status === OrderItemStatus::Cancelled);
        $anyCompleted = $items->contains(fn (OrderItem $item) => $item->status === OrderItemStatus::Completed);
        $anyOpen = $items->contains(fn (OrderItem $item) => ! in_array($item->status, [
            OrderItemStatus::Completed,
            OrderItemStatus::Cancelled,
        ], true));

        if ($allCancelled) {
            $order->update(['status' => OrderStatus::Cancelled]);

            return;
        }

        if ($allCompleted) {
            $order->update(['status' => OrderStatus::Completed]);

            return;
        }

        if ($anyCompleted && $anyOpen) {
            $order->update(['status' => OrderStatus::PartiallyCompleted]);

            return;
        }

        $order->update(['status' => OrderStatus::Pending]);
    }
}
