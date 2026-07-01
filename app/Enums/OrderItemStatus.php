<?php

namespace App\Enums;

enum OrderItemStatus: string
{
    case Pending = 'pending';
    case InProduction = 'in_production';
    case Ready = 'ready';
    case Packed = 'packed';
    case Sent = 'sent';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu',
            self::InProduction => 'Diproduksi',
            self::Ready => 'Siap',
            self::Packed => 'Dikemas',
            self::Sent => 'Dikirim',
            self::Completed => 'Selesai',
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
            self::Sent => self::Completed,
            self::InProduction, self::Ready => null,
            self::Completed => null,
        };
    }

    public function nextForPreOrder(): ?self
    {
        return match ($this) {
            self::Pending => self::InProduction,
            self::InProduction => self::Ready,
            self::Ready => self::Sent,
            self::Sent => self::Completed,
            self::Packed, self::Completed => null,
        };
    }
}
