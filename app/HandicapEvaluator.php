<?php

namespace App;

use App\Enums\EvaluationRecommendation;
use App\Enums\Handicap;
use App\Models\HcHistory;
use App\Models\MatchLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as ModelCollection;
use Illuminate\Support\Collection;

class HandicapEvaluator
{
    public const MAX_EVENTS = 8;

    public const MIN_MATCHES = 20;

    public const MIN_EVENTS = 4;

    public const MIN_OPPONENTS = 5;

    /**
     * @param  Collection<int, MatchLog>|null  $matches
     * @return array<string, mixed>
     */
    public function evaluate(User $user, ?Collection $matches = null): array
    {
        $matches ??= WinRateRanking::completedSingles()->where(function (Builder $query) use ($user): void {
            $query->where('player1_id', $user->id)->orWhere('player2_id', $user->id);
        })->with('funGame')->get();
        $records = new ModelCollection($matches->all());
        $records->loadMissing('funGame');
        $user->loadMissing('hcHistories');
        $lastChange = $user->hcHistories->filter(fn (HcHistory $history): bool => $history->old_hc !== null && $history->old_hc !== $history->new_hc)
            ->sortByDesc('approved_at')->first();
        $completed = $records->filter(fn (MatchLog $match): bool => ! $match->isDouble() && $match->funGame?->format === 'single'
            && $match->funGame->isCompleted() && in_array($user->id, [$match->player1_id, $match->player2_id], true));
        $current = $completed->filter(function (MatchLog $match) use ($user, $lastChange): bool {
            $ownHc = $match->player1_id === $user->id ? $match->player1_hc : $match->player2_hc;

            return $ownHc === $user->handicap && ($lastChange === null || $match->funGame->event_date->toDateString() >= $lastChange->approved_at->toDateString());
        })->sortBy([fn (MatchLog $a, MatchLog $b): int => $b->funGame->event_date <=> $a->funGame->event_date,
            fn (MatchLog $a, MatchLog $b): int => $b->fun_game_id <=> $a->fun_game_id]);
        $eventIds = $current->pluck('fun_game_id')->unique()->take(self::MAX_EVENTS);
        $window = $current->whereIn('fun_game_id', $eventIds);
        $eligible = $window->filter(fn (MatchLog $match): bool => $match->funGame->match_records_complete !== false
            && $match->player1_id !== null && $match->player2_id !== null
            && $match->player1_hc !== null && $match->player2_hc !== null && $match->player1_id !== $match->player2_id
            && in_array($match->winner_id, [$match->player1_id, $match->player2_id], true));

        $byOpponent = $eligible->groupBy(fn (MatchLog $match): int => $match->player1_id === $user->id ? $match->player2_id : $match->player1_id);
        $events = $eligible->groupBy('fun_game_id')->map(function (Collection $eventMatches) use ($user): array {
            $event = $eventMatches->first()->funGame;
            $wins = $eventMatches->where('winner_id', $user->id)->count();

            return ['id' => $event->id, 'title' => $event->title, 'date' => $event->event_date->toDateString(),
                'matches' => $eventMatches->count(), 'wins' => $wins, 'win_rate' => round($wins / $eventMatches->count() * 100, 1)];
        })->values();
        $groups = collect(Handicap::cases())->map(function (Handicap $hc) use ($eligible, $user): array {
            $group = $eligible->filter(fn (MatchLog $match): bool => ($match->player1_id === $user->id ? $match->player2_hc : $match->player1_hc) === $hc);
            $wins = $group->where('winner_id', $user->id)->count();

            return ['handicap' => $hc->value, 'matches' => $group->count(), 'wins' => $wins,
                'win_rate' => $group->isNotEmpty() ? round($wins / $group->count() * 100, 1) : null];
        })->all();
        $total = $eligible->count();
        $wins = $eligible->where('winner_id', $user->id)->count();
        $rawWinRate = $total > 0 ? $wins / $total * 100 : 0.0;
        $winRate = round($rawWinRate, 1);
        $positive = $events->filter(fn (array $event): bool => $event['matches'] < $event['wins'] * 2)->count();
        $negative = $events->filter(fn (array $event): bool => $event['matches'] > $event['wins'] * 2)->count();
        $requiredConsistent = max(3, (int) ceil($events->count() / 2));
        $recent = $this->rate($events->take(4));
        $previous = $this->rate($events->slice(4));
        $missing = [];
        if ($total < self::MIN_MATCHES) {
            $missing[] = 'Perlu '.(self::MIN_MATCHES - $total).' match lagi';
        }
        if ($events->count() < self::MIN_EVENTS) {
            $missing[] = 'Perlu '.(self::MIN_EVENTS - $events->count()).' event lagi';
        }
        if ($byOpponent->count() < self::MIN_OPPONENTS) {
            $missing[] = 'Perlu '.(self::MIN_OPPONENTS - $byOpponent->count()).' lawan berbeda lagi';
        }
        $recommendation = EvaluationRecommendation::Stay;
        $suggested = $user->handicap;
        $reasons = [];
        if ($missing !== []) {
            $recommendation = EvaluationRecommendation::Watch;
            $reasons[] = 'Bukti belum cukup untuk menyarankan perubahan HC.';
        } elseif ($rawWinRate >= 65 && $user->handicap->next() !== null) {
            if ($positive >= $requiredConsistent && ($previous === null || $recent >= 50)) {
                $recommendation = EvaluationRecommendation::Promote;
                $suggested = $user->handicap->next();
                $reasons[] = 'Win rate minimal 65% dengan hasil positif pada '.$positive.' dari '.$events->count().' event.';
            } else {
                $recommendation = EvaluationRecommendation::Watch;
                $reasons[] = 'Win rate tinggi, tetapi hasil antar-event atau performa terbaru belum konsisten.';
            }
        } elseif ($rawWinRate <= 35 && $user->handicap->previous() !== null) {
            if ($negative >= $requiredConsistent && ($previous === null || $recent <= 50)) {
                $recommendation = EvaluationRecommendation::Demote;
                $suggested = $user->handicap->previous();
                $reasons[] = 'Win rate maksimal 35% dengan hasil negatif pada '.$negative.' dari '.$events->count().' event; tinjau ulang setelah ada hasil baru.';
            } else {
                $recommendation = EvaluationRecommendation::Watch;
                $reasons[] = 'Hasil keseluruhan rendah, tetapi bukti penurunan yang konsisten belum cukup.';
            }
        } else {
            $reasons[] = 'Data cukup; performa mendukung HC saat ini atau sudah berada di batas level.';
        }
        $excluded = $window->count() - $total;
        if ($excluded > 0) {
            $reasons[] = $excluded.' catatan dalam periode dipisahkan karena histori event belum lengkap atau data HC/hasil belum valid.';
        }
        $evidence = $total.' match / '.$events->count().' event / '.$byOpponent->count().' lawan di HC '.$user->handicap->value
            .': '.$wins.'W - '.($total - $wins).'L ('.$winRate.'%). '.$reasons[0];

        return [
            'total_matches' => $total, 'wins' => $wins, 'losses' => $total - $wins, 'win_rate' => $winRate,
            'wins_vs_higher' => $eligible->where('winner_id', $user->id)->filter(fn (MatchLog $match): bool => $match->isGiantKiller())->count(),
            'recommendation' => $recommendation, 'suggested_hc' => $suggested, 'evidence' => $evidence,
            'events_count' => $events->count(), 'opponents_count' => $byOpponent->count(), 'sample_sufficient' => $missing === [],
            'missing' => $missing, 'reasons' => $reasons, 'event_results' => $events->all(), 'opponent_results' => $groups,
            'positive_events' => $positive, 'negative_events' => $negative, 'recent_win_rate' => $recent, 'previous_win_rate' => $previous,
            'excluded_matches' => $excluded, 'other_hc_matches' => $completed->count() - $current->count(),
            'unknown_voor_matches' => $eligible->whereNull('voor_type')->count(),
            'unknown_race_matches' => $eligible->filter(fn (MatchLog $match): bool => $match->inferredRace() === null)->count(),
            'largest_opponent_share' => $total > 0 ? round(($byOpponent->map->count()->max() ?? 0) / $total * 100, 1) : 0.0,
        ];
    }

    /** @param Collection<int, array<string, mixed>> $events */
    private function rate(Collection $events): ?float
    {
        return $events->sum('matches') > 0 ? round($events->sum('wins') / $events->sum('matches') * 100, 1) : null;
    }
}
