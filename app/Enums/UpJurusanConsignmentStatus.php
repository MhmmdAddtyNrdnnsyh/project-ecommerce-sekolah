<?php

namespace App\Enums;

enum UpJurusanConsignmentStatus: string
{
    case PendingApproval = 'pending_approval';
    case Approved = 'approved';
    case Received = 'received';
    case Completed = 'completed';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PendingApproval => 'Menunggu Approval',
            self::Approved => 'Disetujui',
            self::Received => 'Barang Diterima',
            self::Completed => 'Selesai',
            self::Rejected => 'Ditolak',
            self::Cancelled => 'Dibatalkan',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function isTerminal(): bool
    {
        return match ($this) {
            self::Completed, self::Cancelled, self::Rejected => true,
            default => false,
        };
    }
}
