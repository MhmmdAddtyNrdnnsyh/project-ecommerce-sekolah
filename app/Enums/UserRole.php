<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Seller = 'seller';
    case Buyer = 'buyer';
    case PicketOfficer = 'picket_officer';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Seller => 'Seller',
            self::Buyer => 'Buyer',
            self::PicketOfficer => 'Petugas Piket',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * @return array<int, array{code: string, name: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $role) => [
                'code' => $role->value,
                'name' => $role->label(),
            ],
            self::cases(),
        );
    }
}
