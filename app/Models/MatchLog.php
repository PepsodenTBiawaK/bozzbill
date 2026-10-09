<?php

namespace App\Models;

use App\Enums\Handicap;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MatchLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'fun_game_id',
        'player1_id',
        'player2_id',
        'player1_score',
        'player2_score',
        'winner_id',
        'player1_hc',
        'player2_hc',
        'stage',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'player1_score' => 'integer',
            'player2_score' => 'integer',
            'player1_hc' => Handicap::class,
            'player2_hc' => Handicap::class,
        ];
    }

    public function funGame(): BelongsTo
    {
        return $this->belongsTo(FunGame::class);
    }

    public function player1(): BelongsTo
    {
        return $this->belongsTo(User::class, 'player1_id');
    }

    public function player2(): BelongsTo
    {
        return $this->belongsTo(User::class, 'player2_id');
    }

    public function winner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'winner_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function loserId(): int
    {
        return $this->winner_id === $this->player1_id ? $this->player2_id : $this->player1_id;
    }

    public function winnerScore(): int
    {
        return $this->winner_id === $this->player1_id ? $this->player1_score : $this->player2_score;
    }

    public function loserScore(): int
    {
        return $this->winner_id === $this->player1_id ? $this->player2_score : $this->player1_score;
    }

    public function scoreSummary(): string
    {
        return "{$this->winnerScore()} - {$this->loserScore()}";
    }

    /**
     * Check if winner defeated an opponent with a higher handicap level at match time.
     */
    public function isGiantKiller(): bool
    {
        if ($this->winner_id === $this->player1_id) {
            return $this->player1_hc->isLowerThan($this->player2_hc);
        }

        return $this->player2_hc->isLowerThan($this->player1_hc);
    }
}
