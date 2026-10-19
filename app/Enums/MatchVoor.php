<?php

namespace App\Enums;

enum MatchVoor: string
{
    case None = 'none';
    case OneFrame = 'one_frame';
    case EightUp = 'eight_up';
    case EightConnected = 'eight_connected';
    case SevenUp = 'seven_up';

    public function label(): string
    {
        return match ($this) {
            self::None => 'Tanpa Voor',
            self::OneFrame => 'Voor 1 Frame',
            self::EightUp => 'Voor Bola 8 Naik',
            self::EightConnected => 'Voor Bola 8 Sambung',
            self::SevenUp => 'Voor Bola 7 Naik',
        };
    }

    public static function between(?Handicap $first, ?Handicap $second): ?self
    {
        if ($first === null || $second === null) {
            return null;
        }

        return match (abs($first->rank() - $second->rank())) {
            0 => self::None,
            1 => self::OneFrame,
            2 => self::EightUp,
            3 => self::EightConnected,
            4 => self::SevenUp,
        };
    }

    /** @return list<string> */
    public static function labels(): array
    {
        return array_map(fn (self $voor): string => $voor->label(), self::cases());
    }
}
