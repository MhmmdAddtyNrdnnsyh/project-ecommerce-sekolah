<?php

namespace App\Support;

use App\Enums\OrderItemStatus;
use App\Enums\PaymentStatus;
use App\Models\OrderItem;
use Illuminate\Validation\ValidationException;

class OrderItemFulfillment
{
    public static function expectedNext(OrderItem $item): ?OrderItemStatus
    {
        return $item->is_pre_order
            ? $item->status->nextForPreOrder()
            : $item->status->next();
    }

    public static function assertCanAdvance(OrderItem $item, OrderItemStatus $newStatus): void
    {
        if ($item->payment_status === PaymentStatus::Rejected) {
            throw ValidationException::withMessages([
                'status' => 'Item dengan pembayaran ditolak tidak dapat diproses pengirimannya.',
            ]);
        }

        if ($item->payment_status !== PaymentStatus::Paid) {
            throw ValidationException::withMessages([
                'status' => 'Pelunasan harus dikonfirmasi sebelum status pengiriman diubah.',
            ]);
        }

        if ($newStatus === OrderItemStatus::Completed) {
            throw ValidationException::withMessages([
                'status' => 'Pesanan selesai hanya bisa dikonfirmasi oleh buyer setelah diterima.',
            ]);
        }

        if ($newStatus === OrderItemStatus::Cancelled) {
            throw ValidationException::withMessages([
                'status' => 'Pembatalan item tidak tersedia melalui pembaruan status pengiriman.',
            ]);
        }

        $expectedNext = self::expectedNext($item);

        if ($expectedNext === null || $newStatus !== $expectedNext) {
            throw ValidationException::withMessages([
                'status' => 'Status tidak valid untuk alur pengiriman item ini.',
            ]);
        }
    }

    public static function assertCanComplete(OrderItem $item): void
    {
        if ($item->status !== OrderItemStatus::Sent) {
            throw ValidationException::withMessages([
                'order' => 'Hanya item berstatus dikirim yang dapat diselesaikan.',
            ]);
        }

        if ($item->payment_status !== PaymentStatus::Paid) {
            throw ValidationException::withMessages([
                'order' => 'Item harus lunas sebelum ditandai selesai.',
            ]);
        }
    }

    /**
     * @return list<string>
     */
    public static function allowedFulfillmentStatusValues(bool $isPreOrder): array
    {
        if ($isPreOrder) {
            return [
                OrderItemStatus::InProduction->value,
                OrderItemStatus::Ready->value,
                OrderItemStatus::Sent->value,
            ];
        }

        return [
            OrderItemStatus::Packed->value,
            OrderItemStatus::Sent->value,
        ];
    }

    /**
     * @return list<string>
     */
    public static function allowedNextStatusValues(OrderItem $item): array
    {
        $next = self::expectedNext($item);

        return $next === null ? [] : [$next->value];
    }
}
