<?php

namespace App\Support;

use App\Enums\OrderItemStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderLivenessService
{
    public const int FULFILLMENT_IDLE_HOURS = 48;

    public const int SENT_IDLE_HOURS = 72;

    /**
     * @return list<string>
     */
    public static function filterValues(): array
    {
        return ['active', 'expired', 'stuck', 'requires_action'];
    }

    public static function unpaidExpiredQuery(?CarbonInterface $now = null): Builder
    {
        $now ??= now();

        return Order::query()
            ->where(function (Builder $query) use ($now) {
                $query->where(function (Builder $inner) use ($now) {
                    $inner->whereNotNull('expires_at')
                        ->where('expires_at', '<=', $now)
                        ->whereHas('items', fn (Builder $items) => $items
                            ->where('payment_status', PaymentStatus::Unpaid)
                            ->whereNotIn('status', [
                                OrderItemStatus::Cancelled->value,
                                OrderItemStatus::Completed->value,
                            ]));
                })->orWhereHas('items', function (Builder $items) use ($now) {
                    $items->where('payment_status', PaymentStatus::Unpaid)
                        ->whereNotIn('status', [
                            OrderItemStatus::Cancelled->value,
                            OrderItemStatus::Completed->value,
                        ])
                        ->where('created_at', '<=', $now->copy()->subHours(OrderItemCancellation::UNPAID_EXPIRY_HOURS))
                        ->whereHas('order', fn (Builder $order) => $order->whereNull('expires_at'));
                });
            });
    }

    public static function stuckFulfillmentQuery(?CarbonInterface $now = null): Builder
    {
        $now ??= now();
        $threshold = $now->copy()->subHours(self::FULFILLMENT_IDLE_HOURS);

        return Order::query()
            ->whereIn('status', ActorLifecycle::activeOrderStatuses())
            ->whereHas('items', function (Builder $items) use ($threshold) {
                $items->where('payment_status', PaymentStatus::Paid)
                    ->whereNotIn('status', [
                        OrderItemStatus::Sent->value,
                        OrderItemStatus::Completed->value,
                        OrderItemStatus::Cancelled->value,
                    ])
                    ->where(function (Builder $q) use ($threshold) {
                        $q->where(function (Builder $inner) use ($threshold) {
                            $inner->whereNotNull('status_changed_at')
                                ->where('status_changed_at', '<=', $threshold);
                        })->orWhere(function (Builder $inner) use ($threshold) {
                            $inner->whereNull('status_changed_at')
                                ->whereNotNull('payment_confirmed_at')
                                ->where('payment_confirmed_at', '<=', $threshold);
                        })->orWhere(function (Builder $inner) use ($threshold) {
                            $inner->whereNull('status_changed_at')
                                ->whereNull('payment_confirmed_at')
                                ->where('updated_at', '<=', $threshold);
                        });
                    });
            });
    }

    public static function stuckSentQuery(?CarbonInterface $now = null): Builder
    {
        $now ??= now();
        $threshold = $now->copy()->subHours(self::SENT_IDLE_HOURS);

        return Order::query()
            ->whereIn('status', ActorLifecycle::activeOrderStatuses())
            ->whereHas('items', function (Builder $items) use ($threshold) {
                $items->where('status', OrderItemStatus::Sent)
                    ->where('payment_status', PaymentStatus::Paid)
                    ->where(function (Builder $q) use ($threshold) {
                        $q->where(function (Builder $inner) use ($threshold) {
                            $inner->whereNotNull('status_changed_at')
                                ->where('status_changed_at', '<=', $threshold);
                        })->orWhere(function (Builder $inner) use ($threshold) {
                            $inner->whereNull('status_changed_at')
                                ->where('updated_at', '<=', $threshold);
                        });
                    });
            });
    }

    public static function stuckQuery(?CarbonInterface $now = null): Builder
    {
        $now ??= now();

        return Order::query()
            ->where(function (Builder $query) use ($now) {
                $query->whereIn('id', self::stuckFulfillmentQuery($now)->select('id'))
                    ->orWhereIn('id', self::stuckSentQuery($now)->select('id'));
            });
    }

    public static function requiresActionQuery(?CarbonInterface $now = null): Builder
    {
        $now ??= now();

        return Order::query()
            ->where(function (Builder $query) use ($now) {
                $query->where('requires_manual_review', true)
                    ->orWhereIn('id', self::unpaidExpiredQuery($now)->select('id'))
                    ->orWhereIn('id', self::stuckQuery($now)->select('id'));
            });
    }

    public static function activeQuery(): Builder
    {
        return Order::query()
            ->whereIn('status', ActorLifecycle::activeOrderStatuses())
            ->where('requires_manual_review', false);
    }

    public static function applyFilter(Builder $query, ?string $filter, ?CarbonInterface $now = null): Builder
    {
        $now ??= now();

        return match ($filter) {
            'active' => $query->whereIn('id', self::activeQuery()->select('id'))
                ->whereNotIn('id', self::requiresActionQuery($now)->select('id')),
            'expired' => $query->whereIn('id', self::unpaidExpiredQuery($now)->select('id')),
            'stuck' => $query->whereIn('id', self::stuckQuery($now)->select('id')),
            'requires_action' => $query->whereIn('id', self::requiresActionQuery($now)->select('id')),
            default => $query,
        };
    }

    /**
     * @return list<string>
     */
    public static function stuckReasonsFor(Order $order, ?CarbonInterface $now = null): array
    {
        $now ??= now();
        $order->loadMissing('items');
        $reasons = [];

        if (self::unpaidExpiredQuery($now)->whereKey($order->id)->exists()) {
            $reasons[] = 'unpaid_expired';
        }

        if (self::stuckFulfillmentQuery($now)->whereKey($order->id)->exists()) {
            $reasons[] = 'fulfillment_idle';
        }

        if (self::stuckSentQuery($now)->whereKey($order->id)->exists()) {
            $reasons[] = 'sent_idle';
        }

        if ($order->requires_manual_review) {
            $reasons[] = 'manual_review';
        }

        return array_values(array_unique($reasons));
    }

    public static function livenessLabel(Order $order, ?CarbonInterface $now = null): string
    {
        $reasons = self::stuckReasonsFor($order, $now);

        if (in_array('manual_review', $reasons, true) || $order->requires_manual_review) {
            return 'requires_action';
        }

        if (in_array('unpaid_expired', $reasons, true)) {
            return 'expired';
        }

        if (array_intersect($reasons, ['fulfillment_idle', 'sent_idle']) !== []) {
            return 'stuck';
        }

        if ($order->status->isTerminal()) {
            return 'closed';
        }

        return 'active';
    }

    public static function detectAndMarkStuck(?CarbonInterface $now = null): int
    {
        $now ??= now();
        $marked = 0;

        $orders = self::detectStuckOrders($now);

        foreach ($orders as $order) {
            $reasons = self::stuckReasonsFor($order, $now);
            $order->update([
                'stuck_detected_at' => $now,
                'stuck_reasons' => $reasons,
            ]);
            $marked++;
        }

        return $marked;
    }

    public static function expireUnpaidOrders(User $actor, ?CarbonInterface $now = null): int
    {
        $now ??= now();
        $cancelled = 0;

        $orders = self::unpaidExpiredQuery($now)->with('items')->get();

        foreach ($orders as $order) {
            $items = $order->items->filter(
                fn (OrderItem $item) => $item->payment_status === PaymentStatus::Unpaid
                    && ! $item->status->isTerminal()
            );

            foreach ($items as $item) {
                OrderItemCancellation::cancelItem(
                    $item,
                    $actor,
                    'Otomatis dibatalkan karena melewati batas waktu pembayaran',
                    true,
                );
                $cancelled++;
            }
        }

        return $cancelled;
    }

    public static function markRequiresManualReview(Order $order, User $actor, ?string $reason = null): void
    {
        if ($actor->role !== UserRole::Admin) {
            throw ValidationException::withMessages([
                'order' => 'Hanya admin yang dapat menandai review manual.',
            ]);
        }

        $order->update([
            'requires_manual_review' => true,
            'requires_manual_review_at' => now(),
            'requires_manual_review_reason' => $reason ?? 'Ditandai butuh peninjauan manual',
            'stuck_detected_at' => $order->stuck_detected_at ?? now(),
            'stuck_reasons' => array_values(array_unique([
                ...($order->stuck_reasons ?? []),
                'manual_review',
            ])),
        ]);
    }

    public static function clearManualReview(Order $order, User $actor): void
    {
        if ($actor->role !== UserRole::Admin) {
            throw ValidationException::withMessages([
                'order' => 'Hanya admin yang dapat menghapus peninjauan manual.',
            ]);
        }

        $reasons = collect($order->stuck_reasons ?? [])
            ->reject(fn ($reason) => $reason === 'manual_review')
            ->values()
            ->all();

        $order->update([
            'requires_manual_review' => false,
            'requires_manual_review_at' => null,
            'requires_manual_review_reason' => null,
            'stuck_reasons' => $reasons === [] ? null : $reasons,
        ]);
    }

    public static function forceComplete(Order $order, User $actor, ?string $reason = null): void
    {
        if ($actor->role !== UserRole::Admin) {
            throw ValidationException::withMessages([
                'order' => 'Hanya admin yang dapat memaksa menyelesaikan pesanan.',
            ]);
        }

        DB::transaction(function () use ($order, $actor, $reason) {
            /** @var Order $current */
            $current = Order::query()
                ->with('items')
                ->lockForUpdate()
                ->findOrFail($order->id);

            $completable = $current->items->filter(
                fn (OrderItem $item) => $item->status === OrderItemStatus::Sent
                    && $item->payment_status === PaymentStatus::Paid
            );

            if ($completable->isEmpty()) {
                throw ValidationException::withMessages([
                    'order' => 'Tidak ada item berstatus dikirim dan lunas yang dapat diselesaikan.',
                ]);
            }

            foreach ($completable as $item) {
                $item->update([
                    'status' => OrderItemStatus::Completed,
                    'status_changed_at' => now(),
                ]);
            }

            OrderStatusSync::sync($current->fresh(['items']));

            $current->refresh();
            $current->update([
                'requires_manual_review' => false,
                'requires_manual_review_at' => null,
                'requires_manual_review_reason' => $reason,
                'stuck_detected_at' => null,
                'stuck_reasons' => null,
            ]);
        });
    }

    public static function forceCancel(Order $order, User $actor, ?string $reason = null): void
    {
        if ($actor->role !== UserRole::Admin) {
            throw ValidationException::withMessages([
                'order' => 'Hanya admin yang dapat memaksa membatalkan pesanan.',
            ]);
        }

        OrderItemCancellation::cancelOrder(
            $order,
            $actor,
            $reason ?? 'Force cancel oleh admin (liveness)',
            true,
        );

        $order->refresh()->update([
            'requires_manual_review' => false,
            'requires_manual_review_at' => null,
            'stuck_detected_at' => null,
            'stuck_reasons' => null,
        ]);
    }

    /**
     * @return Collection<int, Order>
     */
    public static function detectStuckOrders(?CarbonInterface $now = null): Collection
    {
        $now ??= now();

        return Order::query()
            ->where(function (Builder $query) use ($now) {
                $query->whereIn('id', self::stuckQuery($now)->select('id'))
                    ->orWhereIn('id', self::unpaidExpiredQuery($now)->select('id'));
            })
            ->with('items')
            ->orderByDesc('id')
            ->get();
    }
}
