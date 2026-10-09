<?php

namespace App\Models;

use App\Enums\FunGameStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FunGame extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
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
        ];
    }

    public function matches(): HasMany
    {
        return $this->hasMany(MatchLog::class);
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
