<?php

namespace App\Support;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderItem;

class OrderPaymentSync
{
    public static function sync(Order $order): void
    {
        $items = $order->items()
            ->select([
                'id',
                'order_id',
                'payment_status',
                'payment_confirmed_at',
                'payment_confirmed_by',
                'payment_rejection_reason',
            ])
            ->get();

        if ($items->isEmpty()) {
            $order->update([
                'payment_status' => PaymentStatus::Unpaid,
                'payment_confirmed_at' => null,
                'payment_confirmed_by' => null,
                'payment_rejection_reason' => null,
            ]);

            return;
        }

        if ($items->contains(fn (OrderItem $item) => $item->payment_status === PaymentStatus::Rejected)) {
            $rejectedItem = $items->first(fn (OrderItem $item) => $item->payment_status === PaymentStatus::Rejected);

            $order->update([
                'payment_status' => PaymentStatus::Rejected,
                'payment_confirmed_at' => null,
                'payment_confirmed_by' => null,
                'payment_rejection_reason' => $rejectedItem?->payment_rejection_reason,
            ]);

            return;
        }

        if ($items->every(fn (OrderItem $item) => $item->payment_status === PaymentStatus::Paid)) {
            $latestConfirmation = $items
                ->filter(fn (OrderItem $item) => $item->payment_confirmed_at !== null)
                ->sortByDesc('payment_confirmed_at')
                ->first();

            $order->update([
                'payment_status' => PaymentStatus::Paid,
                'payment_confirmed_at' => $latestConfirmation?->payment_confirmed_at,
                'payment_confirmed_by' => $latestConfirmation?->payment_confirmed_by,
                'payment_rejection_reason' => null,
            ]);

            return;
        }

        $order->update([
            'payment_status' => $items->contains(fn (OrderItem $item) => $item->payment_status === PaymentStatus::Paid)
                ? PaymentStatus::PendingConfirmation
                : PaymentStatus::Unpaid,
            'payment_confirmed_at' => null,
            'payment_confirmed_by' => null,
            'payment_rejection_reason' => null,
        ]);
    }
}
