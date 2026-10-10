<?php

use App\Models\Badge;
use App\Models\User;
use Database\Seeders\ProductionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('production setup seeds achievement definitions without demo accounts or activity', function () {
    $this->seed(ProductionSeeder::class);

    expect(Badge::orderBy('slug')->pluck('slug')->all())->toBe([
        'champion', 'champion-2', 'champion-3', 'champion-5', 'drill-master', 'drill-starter', 'electric-form', 'giant-killer', 'sharpshooter',
    ]);
    foreach (['users', 'fun_games', 'match_logs', 'player_drills', 'hc_histories', 'badge_user'] as $table) {
        $this->assertDatabaseEmpty($table);
    }
});

test('repeated production setup preserves earned badges and updates their definitions', function () {
    $this->seed(ProductionSeeder::class);
    $member = User::factory()->create(['phone' => '081234567890']);
    $badge = Badge::where('slug', 'champion')->firstOrFail();
    $badge->update(['name' => 'Old Champion', 'icon' => 'old-icon']);
    $member->badges()->attach($badge, ['awarded_at' => '2026-10-01 12:00:00']);

    $this->seed(ProductionSeeder::class);

    $this->assertDatabaseCount('badges', 9);
    $this->assertDatabaseCount('users', 1);
    $this->assertDatabaseHas('badges', ['id' => $badge->id, 'name' => 'Champion', 'icon' => 'trophy']);
    $this->assertDatabaseHas('badge_user', [
        'user_id' => $member->id,
        'badge_id' => $badge->id,
        'awarded_at' => '2026-10-01 12:00:00',
    ]);
});
