<?php

namespace App\Support;

use App\Enums\ProductStatus;
use App\Enums\UpJurusanConsignmentStatus;
use App\Models\UpJurusanConsignment;
use App\Models\UpJurusanStockMovement;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class ConsignmentTransitionService
{
    /**
     * pending_approval → approved
     */
    public static function approve(UpJurusanConsignment $consignment, int $commissionRate, ?User $actor = null): void
    {
        self::assertCanTransition($consignment, UpJurusanConsignmentStatus::Approved);
        self::assertCommissionRate($commissionRate);

        $from = $consignment->status;

        $consignment->update([
            'status' => UpJurusanConsignmentStatus::Approved,
            'commission_rate' => $commissionRate,
        ]);
        $consignment->product()->update([
            'status' => ProductStatus::Approved,
            'rejection_reason' => null,
        ]);

        DomainEventService::record(
            DomainEventService::AGGREGATE_CONSIGNMENT,
            $consignment->id,
            'consignment_approved',
            $actor,
            [
                'from_status' => $from->value,
                'to_status' => UpJurusanConsignmentStatus::Approved->value,
            ],
        );
    }

    /**
     * pending_approval → rejected
     */
    public static function reject(UpJurusanConsignment $consignment, string $reason, ?User $actor = null): void
    {
        self::assertCanTransition($consignment, UpJurusanConsignmentStatus::Rejected);

        if ($consignment->sold_quantity > 0) {
            throw ValidationException::withMessages([
                'status' => 'Konsinyasi yang sudah terjual tidak dapat ditolak.',
            ]);
        }

        $from = $consignment->status;

        $consignment->update([
            'status' => UpJurusanConsignmentStatus::Rejected,
            'note' => $reason,
        ]);
        $consignment->product()->update([
            'status' => ProductStatus::Rejected,
            'rejection_reason' => $reason,
        ]);

        DomainEventService::record(
            DomainEventService::AGGREGATE_CONSIGNMENT,
            $consignment->id,
            'consignment_rejected',
            $actor,
            [
                'from_status' => $from->value,
                'to_status' => UpJurusanConsignmentStatus::Rejected->value,
            ],
        );
    }

    /**
     * approved → cancelled (no stock received/sold)
     */
    public static function cancel(UpJurusanConsignment $consignment, ?string $reason = null, ?User $actor = null): void
    {
        self::assertCanTransition($consignment, UpJurusanConsignmentStatus::Cancelled);

        if ($consignment->sold_quantity > 0) {
            throw ValidationException::withMessages([
                'status' => 'Konsinyasi yang sudah terjual tidak dapat dibatalkan.',
            ]);
        }

        if ($consignment->received_quantity > 0) {
            throw ValidationException::withMessages([
                'status' => 'Konsinyasi yang sudah diterima tidak dapat dibatalkan.',
            ]);
        }

        $from = $consignment->status;

        $consignment->update([
            'status' => UpJurusanConsignmentStatus::Cancelled,
            'note' => $reason ?? $consignment->note,
        ]);

        DomainEventService::record(
            DomainEventService::AGGREGATE_CONSIGNMENT,
            $consignment->id,
            'consignment_cancelled',
            $actor,
            [
                'from_status' => $from->value,
                'to_status' => UpJurusanConsignmentStatus::Cancelled->value,
            ],
        );
    }

    /**
     * approved|received → received (partial receive allowed).
     * Creates stock movement type=in.
     */
    public static function receive(UpJurusanConsignment $consignment, int $quantity, User $actor): void
    {
        if ($quantity < 1) {
            throw ValidationException::withMessages([
                'quantity' => 'Jumlah diterima minimal 1.',
            ]);
        }

        if (! self::canReceive($consignment)) {
            throw ValidationException::withMessages([
                'quantity' => 'Barang hanya bisa diterima setelah request disetujui dan belum terminal.',
            ]);
        }

        $nextQuantity = $consignment->received_quantity + $quantity;

        if ($nextQuantity > $consignment->requested_quantity) {
            throw ValidationException::withMessages([
                'quantity' => 'Jumlah diterima tidak boleh melebihi jumlah request.',
            ]);
        }

        $from = $consignment->status;

        $consignment->update([
            'received_quantity' => $nextQuantity,
            'status' => UpJurusanConsignmentStatus::Received,
        ]);

        UpJurusanStockMovement::query()->create([
            'up_jurusan_consignment_id' => $consignment->id,
            'user_id' => $actor->id,
            'type' => 'in',
            'quantity' => $quantity,
        ]);

        DomainEventService::record(
            DomainEventService::AGGREGATE_CONSIGNMENT,
            $consignment->id,
            'consignment_received',
            $actor,
            [
                'from_status' => $from->value,
                'to_status' => UpJurusanConsignmentStatus::Received->value,
                'quantity' => $quantity,
            ],
        );
    }

    /**
     * received → completed (domain: fully sold, received > 0)
     */
    public static function complete(UpJurusanConsignment $consignment, ?User $actor = null): void
    {
        self::assertCanTransition($consignment, UpJurusanConsignmentStatus::Completed);

        if ($consignment->received_quantity <= 0) {
            throw ValidationException::withMessages([
                'status' => 'Konsinyasi belum diterima, tidak dapat diselesaikan.',
            ]);
        }

        if ($consignment->sold_quantity < $consignment->received_quantity) {
            throw ValidationException::withMessages([
                'status' => 'Konsinyasi hanya selesai jika seluruh stok diterima sudah terjual.',
            ]);
        }

        self::assertInvariants($consignment);

        $from = $consignment->status;

        $consignment->update([
            'status' => UpJurusanConsignmentStatus::Completed,
        ]);

        DomainEventService::record(
            DomainEventService::AGGREGATE_CONSIGNMENT,
            $consignment->id,
            'consignment_completed',
            $actor,
            [
                'from_status' => $from->value,
                'to_status' => UpJurusanConsignmentStatus::Completed->value,
            ],
        );
    }

    /**
     * Record consignment sale quantity; auto-complete when sold >= received.
     * Does not create stock movements (caller owns movement rows).
     */
    public static function recordSold(UpJurusanConsignment $consignment, int $quantity, ?User $actor = null): void
    {
        if ($quantity < 1) {
            throw ValidationException::withMessages([
                'quantity' => 'Jumlah penjualan minimal 1.',
            ]);
        }

        if (! in_array($consignment->status, [
            UpJurusanConsignmentStatus::Received,
            UpJurusanConsignmentStatus::Completed,
        ], true)) {
            throw ValidationException::withMessages([
                'quantity' => 'Penjualan hanya dari konsinyasi yang sudah diterima.',
            ]);
        }

        $available = $consignment->received_quantity - $consignment->sold_quantity;

        if ($quantity > $available) {
            throw ValidationException::withMessages([
                'quantity' => 'Jumlah keluar tidak boleh melebihi stok titipan tersedia.',
            ]);
        }

        $from = $consignment->status;
        $newSold = $consignment->sold_quantity + $quantity;
        $status = $newSold >= $consignment->received_quantity
            ? UpJurusanConsignmentStatus::Completed
            : UpJurusanConsignmentStatus::Received;

        $consignment->update([
            'sold_quantity' => $newSold,
            'status' => $status,
        ]);

        self::assertInvariants($consignment->fresh() ?? $consignment);

        DomainEventService::record(
            DomainEventService::AGGREGATE_CONSIGNMENT,
            $consignment->id,
            'consignment_sale_recorded',
            $actor,
            [
                'from_status' => $from->value,
                'to_status' => $status->value,
                'quantity' => $quantity,
            ],
        );
    }

    /**
     * Restore sold quantity after reverse/cancel order; reopen to received when needed.
     */
    public static function restoreSold(UpJurusanConsignment $consignment, int $quantity, ?User $actor = null): void
    {
        if ($quantity < 1) {
            return;
        }

        if (in_array($consignment->status, [
            UpJurusanConsignmentStatus::Rejected,
            UpJurusanConsignmentStatus::Cancelled,
            UpJurusanConsignmentStatus::PendingApproval,
            UpJurusanConsignmentStatus::Approved,
        ], true) && $consignment->received_quantity <= 0) {
            throw ValidationException::withMessages([
                'status' => 'Tidak dapat merestorasi penjualan pada status konsinyasi ini.',
            ]);
        }

        $from = $consignment->status;
        $newSold = max(0, $consignment->sold_quantity - $quantity);
        $status = $newSold >= $consignment->received_quantity && $consignment->received_quantity > 0
            ? UpJurusanConsignmentStatus::Completed
            : ($consignment->received_quantity > 0
                ? UpJurusanConsignmentStatus::Received
                : $consignment->status);

        $consignment->update([
            'sold_quantity' => $newSold,
            'status' => $status,
        ]);

        self::assertInvariants($consignment->fresh() ?? $consignment);

        DomainEventService::record(
            DomainEventService::AGGREGATE_CONSIGNMENT,
            $consignment->id,
            'consignment_sale_restored',
            $actor,
            [
                'from_status' => $from->value,
                'to_status' => $status instanceof UpJurusanConsignmentStatus ? $status->value : (string) $status,
                'quantity' => $quantity,
            ],
        );
    }

    public static function canReceive(UpJurusanConsignment $consignment): bool
    {
        return in_array($consignment->status, [
            UpJurusanConsignmentStatus::Approved,
            UpJurusanConsignmentStatus::Received,
        ], true);
    }

    public static function isTerminal(UpJurusanConsignmentStatus $status): bool
    {
        return match ($status) {
            UpJurusanConsignmentStatus::Completed,
            UpJurusanConsignmentStatus::Cancelled,
            UpJurusanConsignmentStatus::Rejected => true,
            default => false,
        };
    }

    /**
     * @return list<UpJurusanConsignmentStatus>
     */
    public static function allowedTargets(UpJurusanConsignmentStatus $from): array
    {
        return match ($from) {
            UpJurusanConsignmentStatus::PendingApproval => [
                UpJurusanConsignmentStatus::Approved,
                UpJurusanConsignmentStatus::Rejected,
            ],
            UpJurusanConsignmentStatus::Approved => [
                UpJurusanConsignmentStatus::Received,
                UpJurusanConsignmentStatus::Cancelled,
            ],
            UpJurusanConsignmentStatus::Received => [
                UpJurusanConsignmentStatus::Received,
                UpJurusanConsignmentStatus::Completed,
            ],
            UpJurusanConsignmentStatus::Completed,
            UpJurusanConsignmentStatus::Cancelled,
            UpJurusanConsignmentStatus::Rejected => [],
        };
    }

    public static function canTransition(
        UpJurusanConsignmentStatus $from,
        UpJurusanConsignmentStatus $to,
    ): bool {
        return in_array($to, self::allowedTargets($from), true);
    }

    public static function assertCanTransition(
        UpJurusanConsignment $consignment,
        UpJurusanConsignmentStatus $to,
    ): void {
        if (! self::canTransition($consignment->status, $to)) {
            throw ValidationException::withMessages([
                'status' => sprintf(
                    'Transisi status konsinyasi dari %s ke %s tidak diizinkan.',
                    $consignment->status->value,
                    $to->value,
                ),
            ]);
        }
    }

    public static function assertInvariants(UpJurusanConsignment $consignment): void
    {
        if ($consignment->received_quantity > $consignment->requested_quantity) {
            throw ValidationException::withMessages([
                'received_quantity' => 'Jumlah diterima tidak boleh melebihi jumlah request.',
            ]);
        }

        if ($consignment->sold_quantity > $consignment->received_quantity) {
            throw ValidationException::withMessages([
                'sold_quantity' => 'Jumlah terjual tidak boleh melebihi jumlah diterima.',
            ]);
        }

        if (
            $consignment->status === UpJurusanConsignmentStatus::Completed
            && ($consignment->received_quantity <= 0 || $consignment->sold_quantity < $consignment->received_quantity)
        ) {
            throw ValidationException::withMessages([
                'status' => 'Status selesai tidak valid untuk kuantitas konsinyasi ini.',
            ]);
        }
    }

    private static function assertCommissionRate(int $commissionRate): void
    {
        if ($commissionRate < 0 || $commissionRate > 100) {
            throw ValidationException::withMessages([
                'commission_rate' => 'Komisi harus antara 0 dan 100.',
            ]);
        }
    }
}
