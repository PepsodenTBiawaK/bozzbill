<?php

namespace App;

use App\Enums\FunGameStatus;
use App\Models\MatchLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class WinRateRanking
{
    public const MIN_MATCHES = 10;

    public const MIN_EVENTS = 3;

    public static function score(int $wins, int $matches): float
    {
        if ($matches <= 0) {
            return 0.0;
        }

        $proportion = max(0, min($wins, $matches)) / $matches;
        $z = 1.96;
        $squaredZ = $z ** 2;

        return max(0.0, ($proportion + $squaredZ / (2 * $matches)
            - $z * sqrt($proportion * (1 - $proportion) / $matches + $squaredZ / (4 * $matches ** 2)))
            / (1 + $squaredZ / $matches)) * 100;
    }

    public static function completedSingles(): Builder
    {
        return MatchLog::query()->whereNull('team1_id')->whereNull('team2_id')
            ->whereHas('funGame', fn (Builder $query): Builder => $query->where('format', 'single')->where('status', FunGameStatus::Completed));
    }

    /** @return Collection<int, array{total: int, wins: int, events: int, win_rate: float, score: float, eligible: bool}> */
    public static function standings(): Collection
    {
        $matches = self::completedSingles()->get(['id', 'fun_game_id', 'player1_id', 'player2_id', 'winner_id']);
        $byPlayer = collect();
        foreach ($matches as $match) {
            if ($match->player1_id === null || $match->player2_id === null || $match->player1_id === $match->player2_id
                || ! in_array($match->winner_id, [$match->player1_id, $match->player2_id], true)) {
                continue;
            }
            foreach ([$match->player1_id, $match->player2_id] as $playerId) {
                $byPlayer[$playerId] = [...($byPlayer[$playerId] ?? []), $match];
            }
        }

        return $byPlayer->map(function (array $records, int $playerId): array {
            $records = collect($records);
            $total = $records->count();
            $wins = $records->where('winner_id', $playerId)->count();
            $events = $records->pluck('fun_game_id')->unique()->count();

            return [
                'total' => $total, 'wins' => $wins, 'events' => $events,
                'win_rate' => round($wins / $total * 100, 1), 'score' => self::score($wins, $total),
                'eligible' => $total >= self::MIN_MATCHES && $events >= self::MIN_EVENTS,
            ];
        });
    }
}
