<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Member = 'member';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Pengurus / Admin',
            self::Member => 'Member',
        };
    }

    public function isAdmin(): bool
    {
        return $this === self::Admin;
    }
}
