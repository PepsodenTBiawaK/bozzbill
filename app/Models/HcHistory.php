<?php

namespace App\Models;

use App\Enums\Handicap;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HcHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'old_hc',
        'new_hc',
        'reason',
        'changed_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'old_hc' => Handicap::class,
            'new_hc' => Handicap::class,
            'approved_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function changer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
