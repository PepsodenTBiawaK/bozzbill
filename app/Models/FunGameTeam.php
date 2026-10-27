<?php

namespace App\Models;

use Database\Factories\FunGameTeamFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FunGameTeam extends Model
{
    /** @use HasFactory<FunGameTeamFactory> */
    use HasFactory;

    protected $fillable = ['fun_game_id', 'player1_id', 'player2_id'];

    public function funGame(): BelongsTo
    {
        return $this->belongsTo(FunGame::class);
    }

    public function player1(): BelongsTo
    {
        return $this->belongsTo(User::class, 'player1_id')->withTrashed();
    }

    public function player2(): BelongsTo
    {
        return $this->belongsTo(User::class, 'player2_id')->withTrashed();
    }

    public function displayName(): string
    {
        return ($this->player1?->name ?? '-').' & '.($this->player2?->name ?? '-');
    }

    public function matches(): Builder
    {
        return MatchLog::query()->where(function (Builder $query): void {
            $query->where('team1_id', $this->id)->orWhere('team2_id', $this->id);
        });
    }

    public function matchesAsTeam1(): HasMany
    {
        return $this->hasMany(MatchLog::class, 'team1_id');
    }

    public function matchesAsTeam2(): HasMany
    {
        return $this->hasMany(MatchLog::class, 'team2_id');
    }

    public function hasMatches(): bool
    {
        if (array_key_exists('matches_as_team1_count', $this->getAttributes()) && array_key_exists('matches_as_team2_count', $this->getAttributes())) {
            return $this->matches_as_team1_count + $this->matches_as_team2_count > 0;
        }

        return $this->matches()->exists();
    }

    /** @return list<int> */
    public function participantIds(): array
    {
        return [(int) $this->player1_id, (int) $this->player2_id];
    }
}
