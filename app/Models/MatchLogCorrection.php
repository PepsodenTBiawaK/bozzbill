<?php

namespace App\Models;

use Database\Factories\MatchLogCorrectionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MatchLogCorrection extends Model
{
    /** @use HasFactory<MatchLogCorrectionFactory> */
    use HasFactory;

    protected $fillable = [
        'fun_game_id',
        'match_log_id',
        'changed_by',
        'reason',
        'before_values',
        'after_values',
    ];

    protected function casts(): array
    {
        return [
            'before_values' => 'array',
            'after_values' => 'array',
        ];
    }

    public function funGame(): BelongsTo
    {
        return $this->belongsTo(FunGame::class);
    }

    public function matchLog(): BelongsTo
    {
        return $this->belongsTo(MatchLog::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
