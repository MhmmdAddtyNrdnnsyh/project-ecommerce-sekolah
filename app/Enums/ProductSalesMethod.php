<?php

namespace App\Enums;

enum ProductSalesMethod: string
{
    case SelfManaged = 'self_managed';
    case UpJurusan = 'up_jurusan';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
