<?php

namespace App\Enums;

enum UserRole: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Reception = 'reception';

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Master/Owner',
            self::Admin => 'Admin',
            self::Reception => 'Receptionist Desk',
        };
    }

    /**
     * @return array<string, string> value => label
     */
    public static function options(): array
    {
        $out = [];
        foreach (self::cases() as $case) {
            $out[$case->value] = $case->label();
        }
        return $out;
    }
}

