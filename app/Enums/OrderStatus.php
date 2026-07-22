<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Open = 'open';
    case PartiallyPaid = 'partially_paid';
    case Paid = 'paid';
    case PartiallyCompleted = 'partially_completed';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    /**
     * Legacy alias used by older call sites / data before E6.
     */
    public const string LEGACY_PENDING = 'pending';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Berjalan',
            self::PartiallyPaid => 'Sebagian dibayar',
            self::Paid => 'Lunas',
            self::PartiallyCompleted => 'Sebagian selesai',
            self::Completed => 'Selesai',
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
            self::Completed, self::Cancelled => true,
            default => false,
        };
    }

    public static function fromStorage(string $value): self
    {
        if ($value === self::LEGACY_PENDING) {
            return self::Open;
        }

        return self::from($value);
    }
}
