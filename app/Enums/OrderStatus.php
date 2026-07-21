<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case PartiallyCompleted = 'partially_completed';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Berjalan',
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
}
