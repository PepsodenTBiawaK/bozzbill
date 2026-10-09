<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Badge extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'badge_user')
            ->withPivot('awarded_at')
            ->withTimestamps();
    }
}
