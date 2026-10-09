<?php

namespace App\Models;

use App\Enums\EvaluationRecommendation;
use App\Enums\FunGameStatus;
use App\Enums\Handicap;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'password',
        'handicap',
        'role',
        'is_active',
        'avatar',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'handicap' => Handicap::class,
            'role' => UserRole::class,
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get user's initials (e.g. "Apr Pinky" => "AP", "Fadil" => "F").
     */
    protected function initials(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                $words = preg_split('/\s+/', trim($this->name));
                if (empty($words) || empty($words[0])) {
                    return 'B';
                }

                if (count($words) === 1) {
                    return strtoupper(substr($words[0], 0, 2));
                }

                return strtoupper(substr($words[0], 0, 1).substr(end($words), 0, 1));
            }
        );
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isMember(): bool
    {
        return $this->role === UserRole::Member;
    }

    /**
     * Matches played where user was Player 1.
     */
    public function matchesAsPlayer1(): HasMany
    {
        return $this->hasMany(MatchLog::class, 'player1_id');
    }

    /**
     * Matches played where user was Player 2.
     */
    public function matchesAsPlayer2(): HasMany
    {
        return $this->hasMany(MatchLog::class, 'player2_id');
    }

    /**
     * Matches won by this user.
     */
    public function wonMatches(): HasMany
    {
        return $this->hasMany(MatchLog::class, 'winner_id');
    }

    /**
     * All matches played by this user.
     */
    public function allMatches(): Builder
    {
        return MatchLog::query()->where(function (Builder $query): void {
            $query->where('player1_id', $this->id)
                ->orWhere('player2_id', $this->id);
        });
    }

    /**
     * Handicap progression audit history.
     */
    public function hcHistories(): HasMany
    {
        return $this->hasMany(HcHistory::class)->orderByDesc('approved_at');
    }

    /**
     * Solo practice / drilling journal logs.
     */
    public function playerDrills(): HasMany
    {
        return $this->hasMany(PlayerDrill::class)->orderByDesc('practice_date');
    }

    /**
     * Achievement badges unlocked.
     */
    public function badges(): BelongsToMany
    {
        return $this->belongsToMany(Badge::class, 'badge_user')
            ->withPivot('awarded_at')
            ->withTimestamps();
    }

    /**
     * Calculate total matches count.
     */
    public function totalMatchesCount(): int
    {
        return $this->allMatches()->count();
    }

    /**
     * Calculate total wins count.
     */
    public function totalWinsCount(): int
    {
        return $this->wonMatches()->count();
    }

    /**
     * Calculate total losses count.
     */
    public function totalLossesCount(): int
    {
        return max(0, $this->totalMatchesCount() - $this->totalWinsCount());
    }

    /**
     * Calculate overall win rate percentage.
     */
    public function winRatePercentage(): float
    {
        $total = $this->totalMatchesCount();
        if ($total === 0) {
            return 0.0;
        }

        return round(($this->totalWinsCount() / $total) * 100, 1);
    }

    /**
     * Calculate count of wins against players with a higher handicap rank.
     */
    public function winsAgainstHigherHcCount(): int
    {
        return $this->wonMatches()
            ->get()
            ->filter(fn (MatchLog $match): bool => $match->isGiantKiller())
            ->count();
    }

    /**
     * Count distinct fun games participated in.
     */
    public function funGamesParticipatedCount(): int
    {
        return MatchLog::query()
            ->where(function ($query): void {
                $query->where('player1_id', $this->id)
                    ->orWhere('player2_id', $this->id);
            })
            ->distinct('fun_game_id')
            ->count('fun_game_id');
    }

    /**
     * Get performance summary for the last completed fun games.
     *
     * @return array<int, array<string, mixed>>
     */
    public function recentFunGamesSummary(int $limit = 4): array
    {
        $funGames = FunGame::where('status', FunGameStatus::Completed)
            ->orderByDesc('event_date')
            ->take($limit)
            ->get();

        $summaries = [];

        foreach ($funGames as $fg) {
            $isChampion = $fg->champion_id === $this->id;
            $isRunnerUp = $fg->runner_up_id === $this->id;
            $isThird = $fg->third_place_id === $this->id;

            $matches = MatchLog::where('fun_game_id', $fg->id)
                ->where(function ($q): void {
                    $q->where('player1_id', $this->id)
                        ->orWhere('player2_id', $this->id);
                })
                ->get();

            $wins = $matches->where('winner_id', $this->id)->count();
            $losses = $matches->count() - $wins;

            $placement = null;
            $label = 'Tidak Ikut';
            $badgeStyle = 'default';

            if ($isChampion) {
                $placement = '1st';
                $label = 'Champion';
                $badgeStyle = 'gold';
            } elseif ($isRunnerUp) {
                $placement = '2nd';
                $label = 'Runner-Up';
                $badgeStyle = 'silver';
            } elseif ($isThird) {
                $placement = '3rd';
                $label = '3rd Place';
                $badgeStyle = 'bronze';
            } elseif ($matches->count() > 0) {
                $label = $losses > $wins ? 'Kalah' : "{$wins}W - {$losses}L";
                $badgeStyle = 'participant';
            }

            $summaries[] = [
                'id' => $fg->id,
                'title' => $fg->title,
                'short_title' => preg_match('/#\d+/', $fg->title, $m) ? "FG {$m[0]}" : $fg->title,
                'event_date' => $fg->event_date->format('d M y'),
                'placement' => $placement,
                'label' => $label,
                'badge_style' => $badgeStyle,
                'matches_count' => $matches->count(),
                'wins' => $wins,
                'losses' => $losses,
            ];
        }

        return $summaries;
    }

    /**
     * Calculate overall drill accuracy percentage.
     */
    public function drillsOverallAccuracy(): float
    {
        $totalSuccess = (int) $this->playerDrills()->sum('success_count');
        $totalAttempts = (int) $this->playerDrills()->sum('total_attempts');

        if ($totalAttempts === 0) {
            return 0.0;
        }

        return round(($totalSuccess / $totalAttempts) * 100, 1);
    }

    /**
     * Count drills completed in current month.
     */
    public function drillsThisMonthCount(): int
    {
        return $this->playerDrills()
            ->whereYear('practice_date', now()->year)
            ->whereMonth('practice_date', now()->month)
            ->count();
    }

    /**
     * Get head to head stats against a specific opponent.
     *
     * @return array<string, mixed>
     */
    public function headToHeadWith(User $opponent): array
    {
        $matches = MatchLog::where(function ($q) use ($opponent): void {
            $q->where('player1_id', $this->id)->where('player2_id', $opponent->id);
        })->orWhere(function ($q) use ($opponent): void {
            $q->where('player1_id', $opponent->id)->where('player2_id', $this->id);
        })
            ->with(['funGame', 'winner', 'player1', 'player2'])
            ->latest('created_at')
            ->get();

        $totalMatches = $matches->count();
        $userWins = $matches->where('winner_id', $this->id)->count();
        $opponentWins = $matches->where('winner_id', $opponent->id)->count();

        $userPercentage = $totalMatches > 0 ? (int) round(($userWins / $totalMatches) * 100) : 0;
        $opponentPercentage = $totalMatches > 0 ? (100 - $userPercentage) : 0;

        return [
            'opponent' => $opponent,
            'total_matches' => $totalMatches,
            'user_wins' => $userWins,
            'opponent_wins' => $opponentWins,
            'user_percentage' => $userPercentage,
            'opponent_percentage' => $opponentPercentage,
            'matches' => $matches,
        ];
    }

    /**
     * Evaluate handicap performance based on the last 4 completed Fun Games.
     *
     * @return array<string, mixed>
     */
    public function evaluateHandicap(): array
    {
        $last4FunGames = FunGame::where('status', FunGameStatus::Completed)
            ->latest('event_date')
            ->take(4)
            ->get();

        $funGameIds = $last4FunGames->pluck('id');

        $matches = MatchLog::whereIn('fun_game_id', $funGameIds)
            ->where(function ($q): void {
                $q->where('player1_id', $this->id)
                    ->orWhere('player2_id', $this->id);
            })
            ->get();

        $totalMatches = $matches->count();
        $wins = $matches->where('winner_id', $this->id)->count();
        $losses = $totalMatches - $wins;
        $winRate = $totalMatches > 0 ? round(($wins / $totalMatches) * 100, 1) : 0.0;

        $winsVsHigher = $matches->where('winner_id', $this->id)
            ->filter(fn (MatchLog $m): bool => $m->isGiantKiller())
            ->count();

        // Determine recommendation
        if ($totalMatches < 3) {
            $recommendation = EvaluationRecommendation::Watch;
            $evidence = "Win Rate 4 Event: {$winRate}% ({$wins}W - {$losses}L) | Sampel match sedikit";
            $suggestedHc = $this->handicap;
        } elseif ($winRate >= 65.0 && $this->handicap->next() !== null) {
            $recommendation = EvaluationRecommendation::Promote;
            $suggestedHc = $this->handicap->next();
            $evidence = "Win Rate 4 Event: {$winRate}% ({$wins}W - {$losses}L)".($winsVsHigher > 0 ? " | {$winsVsHigher}x Menang vs HC Lebih Tinggi" : '');
        } elseif ($winRate <= 30.0 && $totalMatches >= 5 && $this->handicap->previous() !== null) {
            $recommendation = EvaluationRecommendation::Demote;
            $suggestedHc = $this->handicap->previous();
            $evidence = "Win Rate 4 Event: {$winRate}% ({$wins}W - {$losses}L) | Performa menurun";
        } else {
            $recommendation = EvaluationRecommendation::Stay;
            $suggestedHc = $this->handicap;
            $evidence = "Win Rate 4 Event: {$winRate}% ({$wins}W - {$losses}L) | Konsisten di level saat ini";
        }

        return [
            'total_matches' => $totalMatches,
            'wins' => $wins,
            'losses' => $losses,
            'win_rate' => $winRate,
            'wins_vs_higher' => $winsVsHigher,
            'recommendation' => $recommendation,
            'evidence' => $evidence,
            'suggested_hc' => $suggestedHc,
        ];
    }
}
