<?php

namespace App\Support;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentTransitionService
{
    /**
     * unpaid|pending_confirmation → paid
     */
    public static function approve(
        OrderItem $item,
        User $actor,
        PaymentMethod $method = PaymentMethod::Cash,
    ): void {
        DB::transaction(function () use ($item, $actor, $method) {
            /** @var OrderItem $current */
            $current = OrderItem::query()
                ->with(['order:id'])
                ->lockForUpdate()
                ->findOrFail($item->id);

            self::assertCanTransition($current, PaymentStatus::Paid);

            $from = $current->payment_status;

            $current->update([
                'payment_status' => PaymentStatus::Paid,
                'payment_method' => $method,
                'payment_confirmed_at' => now(),
                'payment_confirmed_by' => $actor->id,
                'payment_rejection_reason' => null,
            ]);

            DomainEventService::record(
                DomainEventService::AGGREGATE_ORDER_ITEM,
                $current->id,
                'payment_approved',
                $actor,
                [
                    'from_status' => $from->value,
                    'to_status' => PaymentStatus::Paid->value,
                ],
            );

            OrderPaymentSync::sync($current->order);
            OrderStatusSync::sync($current->order);
        });
    }

    /**
     * unpaid|pending_confirmation → rejected, then cancel + restock recovery.
     */
    public static function reject(
        OrderItem $item,
        User $actor,
        ?string $reason = null,
    ): void {
        DB::transaction(function () use ($item, $actor, $reason) {
            /** @var OrderItem $current */
            $current = OrderItem::query()
                ->with(['order:id', 'product:id,seller_id,up_jurusan_id,sales_method'])
                ->lockForUpdate()
                ->findOrFail($item->id);

            self::assertCanTransition($current, PaymentStatus::Rejected);

            if ($current->status->isTerminal()) {
                throw ValidationException::withMessages([
                    'payment' => 'Item yang sudah selesai atau dibatalkan tidak dapat ditolak pembayarannya.',
                ]);
            }

            $from = $current->payment_status;

            $current->update([
                'payment_status' => PaymentStatus::Rejected,
                'payment_confirmed_at' => null,
                'payment_confirmed_by' => null,
                'payment_rejection_reason' => $reason,
            ]);

            DomainEventService::record(
                DomainEventService::AGGREGATE_ORDER_ITEM,
                $current->id,
                'payment_rejected',
                $actor,
                [
                    'from_status' => $from->value,
                    'to_status' => PaymentStatus::Rejected->value,
                ],
            );

            OrderPaymentSync::sync($current->order);
            OrderStatusSync::sync($current->order);

            OrderItemCancellation::cancelItem(
                $current->fresh(),
                $actor,
                $reason !== null && $reason !== ''
                    ? $reason
                    : 'Pembayaran ditolak',
            );
        });
    }

    public static function isTerminal(PaymentStatus $status): bool
    {
        return match ($status) {
            PaymentStatus::Paid, PaymentStatus::Rejected => true,
            default => false,
        };
    }

    /**
     * @return list<PaymentStatus>
     */
    public static function allowedTargets(PaymentStatus $from): array
    {
        return match ($from) {
            PaymentStatus::Unpaid, PaymentStatus::PendingConfirmation => [
                PaymentStatus::Paid,
                PaymentStatus::Rejected,
            ],
            PaymentStatus::Paid, PaymentStatus::Rejected => [],
        };
    }

    public static function canTransition(PaymentStatus $from, PaymentStatus $to): bool
    {
        return in_array($to, self::allowedTargets($from), true);
    }

    public static function assertCanTransition(OrderItem $item, PaymentStatus $to): void
    {
        if (! self::canTransition($item->payment_status, $to)) {
            if ($item->payment_status === PaymentStatus::Paid && $to === PaymentStatus::Paid) {
                throw ValidationException::withMessages([
                    'payment' => 'Pembayaran item ini sudah lunas.',
                ]);
            }

            if ($item->payment_status === PaymentStatus::Rejected && $to === PaymentStatus::Rejected) {
                throw ValidationException::withMessages([
                    'payment' => 'Pembayaran item ini sudah ditolak.',
                ]);
            }

            if ($item->payment_status === PaymentStatus::Paid && $to === PaymentStatus::Rejected) {
                throw ValidationException::withMessages([
                    'payment' => 'Pembayaran yang sudah lunas tidak dapat ditolak.',
                ]);
            }

            if ($item->payment_status === PaymentStatus::Rejected && $to === PaymentStatus::Paid) {
                throw ValidationException::withMessages([
                    'payment' => 'Pembayaran item ini sudah ditolak.',
                ]);
            }

            throw ValidationException::withMessages([
                'payment' => sprintf(
                    'Transisi pembayaran dari %s ke %s tidak diizinkan.',
                    $item->payment_status->value,
                    $to->value,
                ),
            ]);
        }
    }
}
