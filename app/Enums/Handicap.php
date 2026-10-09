<?php

namespace App\Enums;

enum Handicap: string
{
    case ThreeSb = '3SB';
    case ThreeB = '3B';
    case ThreeN = '3N';
    case ThreeA = '3A';
    case ThreePlus = '3+';

    /**
     * Get numeric rank hierarchy (1 to 5).
     */
    public function rank(): int
    {
        return match ($this) {
            self::ThreeSb => 1,
            self::ThreeB => 2,
            self::ThreeN => 3,
            self::ThreeA => 4,
            self::ThreePlus => 5,
        };
    }

    /**
     * Get descriptive label for the handicap level.
     */
    public function label(): string
    {
        return match ($this) {
            self::ThreeSb => '3SB - Pemula',
            self::ThreeB => '3B - Berkembang',
            self::ThreeN => '3N - Menengah (Standar)',
            self::ThreeA => '3A - Pemain Kuat',
            self::ThreePlus => '3+ - Tingkat Tertinggi',
        };
    }

    /**
     * Check if this handicap is higher than another handicap.
     */
    public function isHigherThan(self $other): bool
    {
        return $this->rank() > $other->rank();
    }

    /**
     * Check if this handicap is lower than another handicap.
     */
    public function isLowerThan(self $other): bool
    {
        return $this->rank() < $other->rank();
    }

    /**
     * Get the next higher handicap level, or null if already at max.
     */
    public function next(): ?self
    {
        return match ($this) {
            self::ThreeSb => self::ThreeB,
            self::ThreeB => self::ThreeN,
            self::ThreeN => self::ThreeA,
            self::ThreeA => self::ThreePlus,
            self::ThreePlus => null,
        };
    }

    /**
     * Get the previous lower handicap level, or null if already at min.
     */
    public function previous(): ?self
    {
        return match ($this) {
            self::ThreePlus => self::ThreeA,
            self::ThreeA => self::ThreeN,
            self::ThreeN => self::ThreeB,
            self::ThreeB => self::ThreeSb,
            self::ThreeSb => null,
        };
    }
}
