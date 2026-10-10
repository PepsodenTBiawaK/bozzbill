<?php

namespace App\Enums;

enum FunGameStatus: string
{
    case Open = 'open';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Sedang Berlangsung',
            self::Completed => 'Selesai',
            self::Cancelled => 'Libur',
        };
    }
}
