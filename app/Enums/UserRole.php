<?php

namespace App\Enums;

enum UserRole: string
{
    case Superadmin = 'superadmin';
    case Admin = 'admin';
    case Member = 'member';

    public function label(): string
    {
        return match ($this) {
            self::Superadmin => 'Superadmin',
            self::Admin => 'Pengurus / Admin',
            self::Member => 'Member',
        };
    }

    public function isAdmin(): bool
    {
        return $this === self::Admin || $this === self::Superadmin;
    }
}
