<?php

namespace App\Models;

use App\Enums\Handicap;
use App\Enums\MatchVoor;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'team1_id',
        'team2_id',
        'winner_team_id',
        'player1_hc',
        'player2_hc',
        'stage',
        'recorded_by',
        'voor_type',
        'voor_recipient_id',
    ];

    protected function casts(): array
    {
        return [
            'player1_score' => 'integer',
            'player2_score' => 'integer',
            'player1_hc' => Handicap::class,
            'player2_hc' => Handicap::class,
            'voor_type' => MatchVoor::class,
            'voor_recipient_id' => 'integer',
        ];
    }

    public function funGame(): BelongsTo
    {
        return $this->belongsTo(FunGame::class);
    }

    public function corrections(): HasMany
    {
        return $this->hasMany(MatchLogCorrection::class);
    }

    /** @return array<string, int|string|null> */
    public function correctionValues(): array
    {
        $attributes = $this->attributesToArray();
        $values = [];

        foreach ([
            'player1_id', 'player2_id', 'team1_id', 'team2_id',
            'player1_score', 'player2_score', 'winner_id', 'winner_team_id',
            'player1_hc', 'player2_hc', 'stage', 'voor_type', 'voor_recipient_id',
        ] as $field) {
            $value = $attributes[$field] ?? null;
            $values[$field] = $value !== null && (str_ends_with($field, '_id') || str_ends_with($field, '_score'))
                ? (int) $value
                : $value;
        }

        return $values;
    }

    public function correctionVersion(): string
    {
        return hash('sha256', json_encode([
            'id' => (int) $this->id,
            'fun_game_id' => (int) $this->fun_game_id,
            'updated_at' => $this->getRawOriginal('updated_at'),
            'values' => $this->correctionValues(),
            'last_correction_id' => $this->corrections()->max('id'),
        ], JSON_THROW_ON_ERROR));
    }

    public function player1(): BelongsTo
    {
        return $this->belongsTo(User::class, 'player1_id')->withTrashed();
    }

    public function player2(): BelongsTo
    {
        return $this->belongsTo(User::class, 'player2_id')->withTrashed();
    }

    public function winner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'winner_id')->withTrashed();
    }

    public function team1(): BelongsTo
    {
        return $this->belongsTo(FunGameTeam::class, 'team1_id');
    }

    public function team2(): BelongsTo
    {
        return $this->belongsTo(FunGameTeam::class, 'team2_id');
    }

    public function winnerTeam(): BelongsTo
    {
        return $this->belongsTo(FunGameTeam::class, 'winner_team_id');
    }

    public function isDouble(): bool
    {
        return $this->team1_id !== null || $this->team2_id !== null;
    }

    public function inferredRace(): ?int
    {
        if (! $this->hasRecordedScore() || $this->player1_score === $this->player2_score
            || ($this->isDouble()
                ? ! in_array($this->winner_team_id, array_filter([$this->team1_id, $this->team2_id]), true)
                : ! in_array($this->winner_id, array_filter([$this->player1_id, $this->player2_id]), true))) {
            return null;
        }

        $score = $this->winnerScore();

        return in_array($score, [3, 4, 5], true) && $score === max($this->player1_score, $this->player2_score)
            ? $score : null;
    }

    public function voorSummary(): string
    {
        if ($this->isDouble()) {
            return 'Tanpa Voor';
        }

        if ($this->voor_type === null) {
            return 'Voor tidak tercatat';
        }

        if ($this->voor_type === MatchVoor::None) {
            return $this->voor_type->label();
        }

        $recipient = $this->voor_recipient_id === $this->player1_id ? $this->player1 : $this->player2;

        return $this->voor_type->label().' untuk '.($recipient?->name ?? 'anggota');
    }

    public function sideName(int $side): string
    {
        if ($this->isDouble()) {
            return ($side === 1 ? $this->team1 : $this->team2)?->displayName() ?? '-';
        }

        return ($side === 1 ? $this->player1 : $this->player2)?->name ?? '-';
    }

    public function winnerName(): string
    {
        return $this->isDouble() ? ($this->winnerTeam?->displayName() ?? '-') : ($this->winner?->name ?? '-');
    }

    /** @return list<int> */
    public function participantIds(): array
    {
        if ($this->isDouble()) {
            return array_values(array_unique([
                ...($this->team1?->participantIds() ?? []),
                ...($this->team2?->participantIds() ?? []),
            ]));
        }

        return array_values(array_filter([$this->player1_id, $this->player2_id]));
    }

    public function wonBy(User|int $player): bool
    {
        $playerId = $player instanceof User ? $player->id : $player;

        return $this->isDouble()
            ? in_array($playerId, $this->winnerTeam?->participantIds() ?? [], true)
            : $this->winner_id === $playerId;
    }

    public function opponentNameFor(int $playerId): string
    {
        $isFirstSide = $this->isDouble()
            ? in_array($playerId, $this->team1?->participantIds() ?? [], true)
            : $this->player1_id === $playerId;

        return $this->sideName($isFirstSide ? 2 : 1);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by')->withTrashed();
    }

    public function loserId(): ?int
    {
        if ($this->isDouble()) {
            return null;
        }

        return $this->winner_id === $this->player1_id ? $this->player2_id : $this->player1_id;
    }

    public function winnerScore(): ?int
    {
        $firstSideWon = $this->isDouble() ? $this->winner_team_id === $this->team1_id : $this->winner_id === $this->player1_id;

        return $firstSideWon ? $this->player1_score : $this->player2_score;
    }

    public function loserScore(): ?int
    {
        $firstSideWon = $this->isDouble() ? $this->winner_team_id === $this->team1_id : $this->winner_id === $this->player1_id;

        return $firstSideWon ? $this->player2_score : $this->player1_score;
    }

    public function scoreSummary(): string
    {
        if (! $this->hasRecordedScore()) {
            return 'Skor Tidak Tercatat';
        }

        return "{$this->winnerScore()} - {$this->loserScore()}";
    }

    public function hasRecordedScore(): bool
    {
        return $this->player1_score !== null && $this->player2_score !== null;
    }

    public function scoreFor(?int $playerId = null, string $separator = '-'): string
    {
        if (! $this->hasRecordedScore()) {
            return 'Skor Tidak Tercatat';
        }

        $isSecondSide = $this->isDouble()
            ? in_array($playerId, $this->team2?->participantIds() ?? [], true)
            : $playerId === $this->player2_id;

        return $isSecondSide
            ? "{$this->player2_score} {$separator} {$this->player1_score}"
            : "{$this->player1_score} {$separator} {$this->player2_score}";
    }

    /**
     * Check if winner defeated an opponent with a higher handicap level at match time.
     */
    public function isGiantKiller(): bool
    {
        if ($this->isDouble() || $this->player1_hc === null || $this->player2_hc === null) {
            return false;
        }

        if ($this->winner_id === $this->player1_id) {
            return $this->player1_hc->isLowerThan($this->player2_hc);
        }

        return $this->player2_hc->isLowerThan($this->player1_hc);
    }
}
