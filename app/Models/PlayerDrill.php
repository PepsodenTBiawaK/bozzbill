<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlayerDrill extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'drill_name',
        'success_count',
        'fail_count',
        'total_attempts',
        'accuracy_percentage',
        'practice_date',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'success_count' => 'integer',
            'fail_count' => 'integer',
            'total_attempts' => 'integer',
            'accuracy_percentage' => 'float',
            'practice_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Automatically update total_attempts and accuracy_percentage.
     */
    public static function boot(): void
    {
        parent::boot();

        static::saving(function (PlayerDrill $drill): void {
            $drill->total_attempts = $drill->success_count + $drill->fail_count;
            if ($drill->total_attempts > 0) {
                $drill->accuracy_percentage = round(($drill->success_count / $drill->total_attempts) * 100, 2);
            } else {
                $drill->accuracy_percentage = 0.00;
            }
        });
    }
}
