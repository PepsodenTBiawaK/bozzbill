<?php

namespace App\Enums;

enum EvaluationRecommendation: string
{
    case Promote = 'Naik HC';
    case Watch = 'Pantau';
    case Stay = 'Tetap';
    case Demote = 'Turun HC';

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Promote => 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20',
            self::Watch => 'bg-amber-500/10 text-amber-400 border border-amber-500/20',
            self::Stay => 'bg-zinc-500/10 text-zinc-300 border border-zinc-500/20',
            self::Demote => 'bg-rose-500/10 text-rose-400 border border-rose-500/20',
        };
    }
}
