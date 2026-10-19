<?php

namespace App\Models;

use App\Enums\FunGameStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FunGame extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'format',
        'event_date',
        'status',
        'champion_id',
        'runner_up_id',
        'third_place_id',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'status' => FunGameStatus::class,
            'match_records_complete' => 'boolean',
        ];
    }

    public function matches(): HasMany
    {
        return $this->hasMany(MatchLog::class);
    }

    public function teams(): HasMany
    {
        return $this->hasMany(FunGameTeam::class);
    }

    public function corrections(): HasMany
    {
        return $this->hasMany(MatchLogCorrection::class);
    }

    public function isDouble(): bool
    {
        return $this->format === 'double';
    }

    public function podiumPlayers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'fun_game_podium')
            ->withPivot('position')->orderBy('fun_game_podium.id');
    }

    public function podiumName(int $position): string
    {
        if ($this->isDouble()) {
            return $this->podiumPlayers->where('pivot.position', $position)->pluck('name')->implode(' & ') ?: '-';
        }

        return match ($position) {
            1 => $this->champion?->name ?? '-',
            2 => $this->runnerUp?->name ?? '-',
            3 => $this->thirdPlace?->name ?? '-',
        };
    }

    public function isOpen(): bool
    {
        return $this->status === FunGameStatus::Open;
    }

    public function champion(): BelongsTo
    {
        return $this->belongsTo(User::class, 'champion_id');
    }

    public function runnerUp(): BelongsTo
    {
        return $this->belongsTo(User::class, 'runner_up_id');
    }

    public function thirdPlace(): BelongsTo
    {
        return $this->belongsTo(User::class, 'third_place_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isCompleted(): bool
    {
        return $this->status === FunGameStatus::Completed;
    }

    public function totalMatchesCount(): int
    {
        return $this->matches()->count();
    }
}
