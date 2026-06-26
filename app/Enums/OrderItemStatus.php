<?php

namespace App\Enums;

enum OrderItemStatus: string
{
    case Pending = 'pending';
    case Packed = 'packed';
    case Sent = 'sent';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu',
            self::Packed => 'Dikemas',
            self::Sent => 'Dikirim',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function next(): ?self
    {
        return match ($this) {
            self::Pending => self::Packed,
            self::Packed => self::Sent,
            self::Sent => null,
        };
    }
}
