<?php

namespace App\Enums;

enum ProductFulfillmentType: string
{
    case ReadyStock = 'ready_stock';
    case PreOrder = 'pre_order';

    public function label(): string
    {
        return match ($this) {
            self::ReadyStock => 'Ready Stock',
            self::PreOrder => 'Pre-Order',
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
