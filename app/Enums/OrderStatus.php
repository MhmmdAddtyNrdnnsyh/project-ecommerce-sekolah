<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
