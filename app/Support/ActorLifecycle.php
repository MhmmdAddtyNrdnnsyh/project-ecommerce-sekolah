<?php

namespace App\Support;

use App\Enums\OrderItemStatus;
use App\Enums\OrderStatus;
use App\Enums\UpJurusanConsignmentStatus;
use App\Enums\UserRole;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\UpJurusan;
use App\Models\UpJurusanConsignment;
use App\Models\UpJurusanPayout;
use App\Models\UpJurusanStockMovement;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class ActorLifecycle
{
    /**
     * @return list<string>
     */
    public static function activeOrderItemStatuses(): array
    {
        return array_values(array_filter(
            OrderItemStatus::values(),
            fn (string $status) => ! OrderItemStatus::from($status)->isTerminal(),
        ));
    }

    /**
     * @return list<string>
     */
    public static function activeOrderStatuses(): array
    {
        return array_values(array_filter(
            OrderStatus::values(),
            fn (string $status) => ! OrderStatus::from($status)->isTerminal(),
        ));
    }

    /**
     * @return list<string>
     */
    public static function openConsignmentStatuses(): array
    {
        return [
            UpJurusanConsignmentStatus::PendingApproval->value,
            UpJurusanConsignmentStatus::Approved->value,
            UpJurusanConsignmentStatus::Received->value,
        ];
    }

    public static function userHasActiveOrders(User $user): bool
    {
        return Order::query()
            ->where('user_id', $user->id)
            ->where(function ($query) {
                $query->whereIn('status', self::activeOrderStatuses())
                    ->orWhereHas('items', fn ($items) => $items
                        ->whereIn('status', self::activeOrderItemStatuses()));
            })
            ->exists();
    }

    public static function userHasActiveSellerOrderItems(User $user): bool
    {
        return OrderItem::query()
            ->whereIn('status', self::activeOrderItemStatuses())
            ->whereHas('product', fn ($q) => $q->where('seller_id', $user->id))
            ->exists();
    }

    public static function userHasOpenConsignments(User $user): bool
    {
        return UpJurusanConsignment::query()
            ->where('seller_id', $user->id)
            ->whereIn('status', self::openConsignmentStatuses())
            ->exists();
    }

    public static function userHasUnpaidPayouts(User $user): bool
    {
        $consignmentIds = UpJurusanConsignment::query()
            ->where('seller_id', $user->id)
            ->pluck('id');

        if ($consignmentIds->isEmpty()) {
            return false;
        }

        foreach ($consignmentIds as $consignmentId) {
            if (self::consignmentUnpaidAmount((int) $consignmentId) > 0) {
                return true;
            }
        }

        return false;
    }

    public static function consignmentUnpaidAmount(int $consignmentId): int
    {
        $sellerEarnings = (int) UpJurusanStockMovement::query()
            ->where('up_jurusan_consignment_id', $consignmentId)
            ->where('type', 'out')
            ->sum('seller_amount');

        $paidAmount = (int) UpJurusanPayout::query()
            ->where('up_jurusan_consignment_id', $consignmentId)
            ->sum('amount');

        return max(0, $sellerEarnings - $paidAmount);
    }

    public static function upJurusanHasActiveOrderItems(UpJurusan $upJurusan): bool
    {
        return OrderItem::query()
            ->whereIn('status', self::activeOrderItemStatuses())
            ->whereHas('product', function ($query) use ($upJurusan) {
                $query->where('up_jurusan_id', $upJurusan->id)
                    ->orWhereHas('upJurusanConsignments', fn ($consignments) => $consignments
                        ->where('up_jurusan_id', $upJurusan->id));
            })
            ->exists();
    }

    public static function upJurusanHasOpenConsignments(UpJurusan $upJurusan): bool
    {
        return UpJurusanConsignment::query()
            ->where('up_jurusan_id', $upJurusan->id)
            ->whereIn('status', self::openConsignmentStatuses())
            ->exists();
    }

    public static function upJurusanHasUnpaidPayouts(UpJurusan $upJurusan): bool
    {
        $consignmentIds = UpJurusanConsignment::query()
            ->where('up_jurusan_id', $upJurusan->id)
            ->pluck('id');

        foreach ($consignmentIds as $consignmentId) {
            if (self::consignmentUnpaidAmount((int) $consignmentId) > 0) {
                return true;
            }
        }

        return false;
    }

    public static function assertCanPromoteToSeller(User $user): void
    {
        if ($user->role !== UserRole::Buyer) {
            throw ValidationException::withMessages([
                'application' => 'Hanya akun buyer yang dapat dipromosikan menjadi seller.',
            ]);
        }

        if (self::userHasActiveOrders($user)) {
            throw ValidationException::withMessages([
                'application' => 'Pengajuan seller tidak dapat disetujui selama buyer masih memiliki pesanan aktif.',
            ]);
        }
    }

    public static function assertCanDeleteAccount(User $user): void
    {
        if (self::userHasActiveOrders($user)) {
            throw ValidationException::withMessages([
                'password' => 'Akun tidak dapat dihapus selama masih ada pesanan aktif.',
            ]);
        }

        if (self::userHasActiveSellerOrderItems($user)) {
            throw ValidationException::withMessages([
                'password' => 'Akun tidak dapat dihapus selama masih ada item pesanan seller yang aktif.',
            ]);
        }

        if (self::userHasOpenConsignments($user)) {
            throw ValidationException::withMessages([
                'password' => 'Akun tidak dapat dihapus selama masih ada titipan barang yang aktif.',
            ]);
        }

        if (self::userHasUnpaidPayouts($user)) {
            throw ValidationException::withMessages([
                'password' => 'Akun tidak dapat dihapus selama masih ada saldo pencairan yang belum lunas.',
            ]);
        }

        if ($user->role === UserRole::PicketOfficer && $user->up_jurusan_id !== null) {
            $upJurusan = UpJurusan::query()->find($user->up_jurusan_id);
            if ($upJurusan !== null && self::upJurusanHasActiveOrderItems($upJurusan)) {
                throw ValidationException::withMessages([
                    'password' => 'Akun picket tidak dapat dihapus selama UP Jurusan masih memiliki pesanan aktif.',
                ]);
            }
        }

        if ($user->role === UserRole::AdminJurusan) {
            $hasActiveUp = UpJurusan::query()
                ->where('admin_jurusan_id', $user->id)
                ->get()
                ->contains(fn (UpJurusan $upJurusan) => self::upJurusanHasActiveTransactions($upJurusan));

            if ($hasActiveUp) {
                throw ValidationException::withMessages([
                    'password' => 'Akun admin jurusan tidak dapat dihapus selama UP Jurusan masih memiliki transaksi aktif.',
                ]);
            }
        }
    }

    public static function assertCanReassignPicket(UpJurusan $upJurusan): void
    {
        if (self::upJurusanHasActiveOrderItems($upJurusan)) {
            throw ValidationException::withMessages([
                'picket_id' => 'Picket tidak dapat diganti atau dilepas selama masih ada item pesanan aktif di UP Jurusan ini.',
            ]);
        }
    }

    public static function assertCanDeleteUpJurusan(UpJurusan $upJurusan): void
    {
        if (self::upJurusanHasActiveTransactions($upJurusan)) {
            throw ValidationException::withMessages([
                'up_jurusan' => 'UP Jurusan tidak dapat dihapus selama masih ada transaksi aktif.',
            ]);
        }
    }

    public static function upJurusanHasActiveTransactions(UpJurusan $upJurusan): bool
    {
        return self::upJurusanHasActiveOrderItems($upJurusan)
            || self::upJurusanHasOpenConsignments($upJurusan)
            || self::upJurusanHasUnpaidPayouts($upJurusan);
    }
}
