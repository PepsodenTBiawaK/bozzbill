<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Badge extends Model
{
    use HasFactory;

    /** @return array<int, array{name: string, slug: string, description: string, icon: string, metric: string, target: int}> */
    public static function catalog(): array
    {
        return [
            ['name' => 'Sharpshooter', 'slug' => 'sharpshooter', 'description' => 'Mencapai akurasi minimal 80% dalam satu sesi drilling.', 'icon' => 'target', 'metric' => 'accuracy', 'target' => 80],
            ['name' => 'Electric Form', 'slug' => 'electric-form', 'description' => 'Tercatat bermain atau meraih podium di 4 Fun Game selesai berturut-turut.', 'icon' => 'zap', 'metric' => 'streak', 'target' => 4],
            ['name' => 'Champion', 'slug' => 'champion', 'description' => 'Meraih Juara 1 pada 1 Fun Game resmi komunitas.', 'icon' => 'trophy', 'metric' => 'champions', 'target' => 1],
            ['name' => 'Champion 2×', 'slug' => 'champion-2', 'description' => 'Meraih Juara 1 pada 2 Fun Game resmi komunitas.', 'icon' => 'trophy', 'metric' => 'champions', 'target' => 2],
            ['name' => 'Champion 3×', 'slug' => 'champion-3', 'description' => 'Meraih Juara 1 pada 3 Fun Game resmi komunitas.', 'icon' => 'trophy', 'metric' => 'champions', 'target' => 3],
            ['name' => 'Champion 5×', 'slug' => 'champion-5', 'description' => 'Meraih Juara 1 pada 5 Fun Game resmi komunitas.', 'icon' => 'trophy', 'metric' => 'champions', 'target' => 5],
            ['name' => 'Giant Killer', 'slug' => 'giant-killer', 'description' => 'Menang melawan pemain dengan HC lebih tinggi saat match berlangsung.', 'icon' => 'swords', 'metric' => 'giant_kills', 'target' => 1],
            ['name' => 'Drill Starter', 'slug' => 'drill-starter', 'description' => 'Mencatat 10 sesi latihan drilling mandiri.', 'icon' => 'target', 'metric' => 'drill_sessions', 'target' => 10],
            ['name' => 'Drill Master', 'slug' => 'drill-master', 'description' => 'Mencatat 50 sesi latihan drilling mandiri.', 'icon' => 'flame', 'metric' => 'drill_sessions', 'target' => 50],
        ];
    }

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
