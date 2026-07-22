<?php

namespace App\Support;

use App\Enums\OrderItemStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Collection;

class OrderSettlementService
{
    /**
     * Derive and persist order header status from all order items.
     * Single source of truth for multi-owner settlement.
     */
    public static function sync(Order $order): void
    {
        $items = self::loadItems($order);
        $status = self::deriveStatus($items);

        if ($order->status !== $status) {
            $old = $order->status;
            $order->update(['status' => $status]);

            DomainEventService::record(
                DomainEventService::AGGREGATE_ORDER,
                $order->id,
                'order_status_changed',
                null,
                [
                    'old_status' => $old instanceof OrderStatus ? $old->value : (string) $old,
                    'new_status' => $status->value,
                ],
            );
        }
    }

    /**
     * @param  Collection<int, OrderItem>|iterable<int, OrderItem>  $items
     */
    public static function deriveStatus(iterable $items): OrderStatus
    {
        $items = Collection::make($items);

        if ($items->isEmpty()) {
            return OrderStatus::Open;
        }

        $statuses = $items->map(fn (OrderItem $item) => $item->status);
        $payments = $items->map(fn (OrderItem $item) => $item->payment_status);

        $allCancelled = $statuses->every(fn ($status) => $status === OrderItemStatus::Cancelled);
        if ($allCancelled) {
            return OrderStatus::Cancelled;
        }

        $allCompleted = $statuses->every(fn ($status) => $status === OrderItemStatus::Completed);
        if ($allCompleted) {
            return OrderStatus::Completed;
        }

        $allTerminal = $statuses->every(
            fn ($status) => in_array($status, [OrderItemStatus::Completed, OrderItemStatus::Cancelled], true)
        );
        if ($allTerminal) {
            // Mix completed + cancelled: order is terminal success for remaining items.
            return OrderStatus::Completed;
        }

        $anyCompleted = $statuses->contains(OrderItemStatus::Completed);
        $anyOpen = $statuses->contains(
            fn ($status) => ! in_array($status, [OrderItemStatus::Completed, OrderItemStatus::Cancelled], true)
        );

        if ($anyCompleted && $anyOpen) {
            return OrderStatus::PartiallyCompleted;
        }

        $active = $items->reject(fn (OrderItem $item) => $item->status === OrderItemStatus::Cancelled);

        if ($active->isEmpty()) {
            return OrderStatus::Cancelled;
        }

        $activePayments = $active->map(fn (OrderItem $item) => $item->payment_status);
        $allPaid = $activePayments->every(fn ($payment) => $payment === PaymentStatus::Paid);
        if ($allPaid) {
            return OrderStatus::Paid;
        }

        $anyPaid = $activePayments->contains(PaymentStatus::Paid);
        if ($anyPaid) {
            return OrderStatus::PartiallyPaid;
        }

        return OrderStatus::Open;
    }

    /**
     * Payload helpers shared by buyer/admin surfaces.
     *
     * @return array{code: string, label: string}
     */
    public static function statusPayload(Order $order): array
    {
        $status = $order->relationLoaded('items')
            ? self::deriveStatus($order->items)
            : $order->status;

        if (! $status instanceof OrderStatus) {
            $status = OrderStatus::fromStorage((string) $status);
        }

        return [
            'code' => $status->value,
            'label' => $status->label(),
        ];
    }

    /**
     * Always re-query so payment/fulfillment mutations are not missed on stale relations.
     *
     * @return Collection<int, OrderItem>
     */
    private static function loadItems(Order $order): Collection
    {
        return $order->items()
            ->select(['id', 'order_id', 'status', 'payment_status'])
            ->get();
    }
}
