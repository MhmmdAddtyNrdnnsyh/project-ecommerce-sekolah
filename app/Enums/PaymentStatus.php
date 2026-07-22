<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Unpaid = 'unpaid';
    case PendingConfirmation = 'pending_confirmation';
    case Paid = 'paid';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Unpaid => 'Belum dibayar',
            self::PendingConfirmation => 'Menunggu konfirmasi',
            self::Paid => 'Lunas',
            self::Rejected => 'Ditolak',
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
            self::Paid, self::Rejected => true,
            default => false,
        };
    }
}
