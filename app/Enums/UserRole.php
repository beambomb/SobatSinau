<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Teacher = 'guru';
    case Student = 'siswa';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Teacher => 'Guru',
            self::Student => 'Siswa',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
