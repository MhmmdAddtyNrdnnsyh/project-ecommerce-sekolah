<?php

namespace App\Support;

use App\Enums\OrderItemStatus;
use App\Enums\PaymentStatus;
use App\Enums\UpJurusanConsignmentStatus;
use App\Enums\UserRole;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\UpJurusanConsignment;
use App\Models\UpJurusanStockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderItemCancellation
{
    public const int UNPAID_EXPIRY_HOURS = 24;

    public static function cancelItem(
        OrderItem $item,
        User $actor,
        ?string $reason = null,
        bool $force = false,
    ): void {
        DB::transaction(function () use ($item, $actor, $reason, $force) {
            self::cancelItemWithinTransaction($item->id, $actor, $reason, $force);
        });
    }

    public static function cancelOrder(
        Order $order,
        User $actor,
        ?string $reason = null,
        bool $force = false,
    ): void {
        DB::transaction(function () use ($order, $actor, $reason, $force) {
            /** @var Order $current */
            $current = Order::query()
                ->with('items:id,order_id,status')
                ->lockForUpdate()
                ->findOrFail($order->id);

            $cancellableIds = $current->items
                ->filter(fn (OrderItem $item) => $item->status !== OrderItemStatus::Cancelled)
                ->pluck('id');

            if ($cancellableIds->isEmpty()) {
                throw ValidationException::withMessages([
                    'order' => 'Pesanan ini sudah dibatalkan.',
                ]);
            }

            foreach ($cancellableIds as $itemId) {
                self::cancelItemWithinTransaction((int) $itemId, $actor, $reason, $force);
            }
        });
    }

    private static function cancelItemWithinTransaction(
        int $itemId,
        User $actor,
        ?string $reason,
        bool $force,
    ): void {
        /** @var OrderItem $current */
        $current = OrderItem::query()
            ->with([
                'order:id,user_id,status',
                'product:id,seller_id,up_jurusan_id,sales_method,stock,fulfillment_type',
            ])
            ->lockForUpdate()
            ->findOrFail($itemId);

        if ($current->status === OrderItemStatus::Cancelled) {
            return;
        }

        self::assertCanCancel($current, $actor, $force);
        self::restock($current, $actor);

        $current->update([
            'status' => OrderItemStatus::Cancelled,
            'cancelled_at' => now(),
            'cancelled_by' => $actor->id,
            'cancel_reason' => $reason,
        ]);

        OrderPaymentSync::sync($current->order);
        OrderStatusSync::sync($current->order->fresh(['items']));

        $order = $current->order->fresh(['items']);
        if ($order->items->every(fn (OrderItem $orderItem) => $orderItem->status === OrderItemStatus::Cancelled)) {
            $order->update([
                'cancelled_at' => now(),
                'cancelled_by' => $actor->id,
                'cancel_reason' => $reason,
            ]);
        }
    }

    public static function assertCanCancel(OrderItem $item, User $actor, bool $force = false): void
    {
        if ($item->status === OrderItemStatus::Cancelled) {
            throw ValidationException::withMessages([
                'order' => 'Item pesanan sudah dibatalkan.',
            ]);
        }

        if ($item->status === OrderItemStatus::Completed) {
            throw ValidationException::withMessages([
                'order' => 'Item yang sudah selesai tidak dapat dibatalkan.',
            ]);
        }

        if ($force) {
            if ($actor->role !== UserRole::Admin) {
                throw ValidationException::withMessages([
                    'order' => 'Hanya admin yang dapat memaksa pembatalan item ini.',
                ]);
            }

            return;
        }

        if (
            $item->payment_status === PaymentStatus::Paid
            && $item->status === OrderItemStatus::Sent
        ) {
            throw ValidationException::withMessages([
                'order' => 'Item yang sudah dibayar dan dikirim hanya dapat dibatalkan oleh admin.',
            ]);
        }

        match ($actor->role) {
            UserRole::Buyer => self::assertBuyerCanCancel($item, $actor),
            UserRole::Seller => self::assertSellerCanCancel($item, $actor),
            UserRole::PicketOfficer => self::assertPicketCanCancel($item, $actor),
            UserRole::Admin => null,
            default => throw ValidationException::withMessages([
                'order' => 'Anda tidak berwenang membatalkan item pesanan ini.',
            ]),
        };
    }

    private static function assertBuyerCanCancel(OrderItem $item, User $actor): void
    {
        if ($item->order->user_id !== $actor->id) {
            throw ValidationException::withMessages([
                'order' => 'Anda tidak berwenang membatalkan pesanan ini.',
            ]);
        }

        if ($item->payment_status === PaymentStatus::Paid) {
            throw ValidationException::withMessages([
                'order' => 'Item yang sudah dibayar tidak dapat dibatalkan oleh pembeli.',
            ]);
        }
    }

    private static function assertSellerCanCancel(OrderItem $item, User $actor): void
    {
        if ($item->product->seller_id !== $actor->id) {
            throw ValidationException::withMessages([
                'order' => 'Anda tidak berwenang membatalkan item penjual lain.',
            ]);
        }

        if ($item->product->usesConsignmentStock()) {
            throw ValidationException::withMessages([
                'order' => 'Pembatalan produk titipan dikelola oleh picket officer UP Jurusan.',
            ]);
        }

        if ($item->payment_status === PaymentStatus::Paid && $item->status === OrderItemStatus::Sent) {
            throw ValidationException::withMessages([
                'order' => 'Item yang sudah dibayar dan dikirim hanya dapat dibatalkan oleh admin.',
            ]);
        }
    }

    private static function assertPicketCanCancel(OrderItem $item, User $actor): void
    {
        $product = $item->product;

        if (! $product->usesConsignmentStock()) {
            throw ValidationException::withMessages([
                'order' => 'Picket hanya dapat membatalkan item titipan UP Jurusan.',
            ]);
        }

        if ($actor->up_jurusan_id === null) {
            throw ValidationException::withMessages([
                'order' => 'Anda tidak berwenang membatalkan item di UP Jurusan ini.',
            ]);
        }

        $assigned = $product->up_jurusan_id === $actor->up_jurusan_id
            || UpJurusanConsignment::query()
                ->where('product_id', $product->id)
                ->where('up_jurusan_id', $actor->up_jurusan_id)
                ->exists();

        if (! $assigned) {
            throw ValidationException::withMessages([
                'order' => 'Anda tidak berwenang membatalkan item di UP Jurusan ini.',
            ]);
        }
    }

    public static function restock(OrderItem $item, User $actor): void
    {
        $product = Product::query()
            ->lockForUpdate()
            ->find($item->product_id);

        if ($product === null || $item->is_pre_order) {
            return;
        }

        if ($product->usesConsignmentStock()) {
            self::restockConsignment($item, $product, $actor);

            return;
        }

        $product->update([
            'stock' => $product->stock + $item->quantity,
        ]);

        if ($product->seller_id === null && $product->up_jurusan_id !== null) {
            $outMovements = UpJurusanStockMovement::query()
                ->where('order_id', $item->order_id)
                ->where('product_id', $product->id)
                ->where('type', 'out')
                ->whereNull('reverses_movement_id')
                ->lockForUpdate()
                ->get();

            foreach ($outMovements as $movement) {
                if (self::alreadyReversed($movement)) {
                    continue;
                }

                UpJurusanStockMovement::query()->create([
                    'up_jurusan_consignment_id' => null,
                    'product_id' => $product->id,
                    'order_id' => $item->order_id,
                    'user_id' => $actor->id,
                    'type' => 'in',
                    'quantity' => $movement->quantity,
                    'unit_price' => $movement->unit_price,
                    'gross_amount' => $movement->gross_amount,
                    'commission_amount' => $movement->commission_amount,
                    'seller_amount' => $movement->seller_amount,
                    'note' => 'Restock pembatalan pesanan',
                    'reverses_movement_id' => $movement->id,
                ]);
            }
        }
    }

    private static function restockConsignment(OrderItem $item, Product $product, User $actor): void
    {
        $outMovements = UpJurusanStockMovement::query()
            ->where('order_id', $item->order_id)
            ->where('type', 'out')
            ->whereNotNull('up_jurusan_consignment_id')
            ->whereHas('consignment', fn ($q) => $q->where('product_id', $product->id))
            ->orderByDesc('id')
            ->lockForUpdate()
            ->get();

        $remaining = $item->quantity;

        foreach ($outMovements as $movement) {
            if ($remaining <= 0) {
                break;
            }

            if (self::alreadyReversed($movement)) {
                continue;
            }

            $restoreQty = min($remaining, $movement->quantity);

            /** @var UpJurusanConsignment $consignment */
            $consignment = UpJurusanConsignment::query()
                ->lockForUpdate()
                ->findOrFail($movement->up_jurusan_consignment_id);

            $newSold = max(0, $consignment->sold_quantity - $restoreQty);
            $consignment->update([
                'sold_quantity' => $newSold,
                'status' => $newSold >= $consignment->received_quantity
                    ? UpJurusanConsignmentStatus::Completed
                    : ($consignment->received_quantity > 0
                        ? UpJurusanConsignmentStatus::Received
                        : $consignment->status),
            ]);

            $unitPrice = $movement->unit_price;
            $grossAmount = $unitPrice * $restoreQty;
            $commissionAmount = $movement->quantity > 0
                ? intdiv($movement->commission_amount * $restoreQty, $movement->quantity)
                : 0;

            UpJurusanStockMovement::query()->create([
                'up_jurusan_consignment_id' => $consignment->id,
                'product_id' => null,
                'order_id' => $item->order_id,
                'user_id' => $actor->id,
                'type' => 'in',
                'quantity' => $restoreQty,
                'unit_price' => $unitPrice,
                'gross_amount' => $grossAmount,
                'commission_amount' => $commissionAmount,
                'seller_amount' => $grossAmount - $commissionAmount,
                'note' => 'Restock pembatalan pesanan',
                'reverses_movement_id' => $movement->id,
            ]);

            $remaining -= $restoreQty;
        }
    }

    private static function alreadyReversed(UpJurusanStockMovement $movement): bool
    {
        return UpJurusanStockMovement::query()
            ->where('reverses_movement_id', $movement->id)
            ->exists();
    }
}
