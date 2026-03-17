<?php

namespace App\Enums;

enum RoleType: string
{
    case SUPER_ADMIN = 'super-admin';
    case ADMIN = 'admin';
    case WAREHOUSE = 'warehouse';
    case CUSTOMER = 'customer';

    /**
     * Label yang ramah dibaca (Human-readable) untuk UI.
     */
    public function label(): string
    {
        return match ($this) {
            self::SUPER_ADMIN => 'Super Administrator',
            self::ADMIN => 'Administrator',
            self::WAREHOUSE => 'Staf Gudang',
            self::CUSTOMER => 'Pelanggan',
        };
    }

    public function defaultPermissions(): array
    {
        return match ($this) {
            self::ADMIN => [
                'manage users',
                'manage products',
            ],
            self::WAREHOUSE => [
                'manage warehouse',
            ],
            self::CUSTOMER => [
                'view profile',
            ],
            self::SUPER_ADMIN => [],
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Helper untuk membuat dropdown / select options di Frontend.
     * Mengembalikan array asosiatif: [['value' => 'admin', 'label' => 'Administrator'], ...]
     */
    public static function options(): array
    {
        return array_map(fn(self $case) => [
            'value' => $case->value,
            'label' => $case->label(),
        ], self::cases());
    }
}
