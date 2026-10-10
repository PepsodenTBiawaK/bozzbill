<?php

use App\Enums\FunGameStatus;
use App\Enums\UserRole;
use App\Models\Badge;
use App\Models\FunGame;
use App\Models\PlayerDrill;
use App\Models\User;
use Database\Seeders\ProductionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guests cannot access achievement details', function () {
    $this->get(route('achievements'))->assertRedirect(route('login'));
});

test('home shows three achievements and detail page shows the full catalog with progress', function () {
    $this->seed(ProductionSeeder::class);
    $member = User::factory()->create();
    $other = User::factory()->create();
    $other->badges()->attach(Badge::where('slug', 'champion-5')->firstOrFail());

    $this->actingAs($member)->get(route('home'))->assertOk()
        ->assertSee(route('achievements'))->assertViewHas('achievements', fn ($badges) => $badges->count() === 3);
    $this->get(route('achievements'))->assertOk()->assertSee('Champion 5×')->assertSee('0/5 gelar')
        ->assertViewHas('unlockedCount', 0)->assertViewHas('achievements', fn ($badges) => $badges->count() === 9);
});

test('closing events unlocks champion milestones at the exact goal and deletion removes only unearned tiers', function (int $titles, string $earned, string $locked) {
    $this->seed(ProductionSeeder::class);
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $champion = User::factory()->create();
    for ($number = 1; $number < $titles; $number++) {
        FunGame::create(['title' => 'Gelar '.$number, 'event_date' => '2026-09-01', 'status' => FunGameStatus::Completed, 'champion_id' => $champion->id, 'created_by' => $admin->id]);
    }
    $event = FunGame::create(['title' => 'Gelar berikutnya', 'event_date' => '2026-10-01', 'status' => FunGameStatus::Open, 'created_by' => $admin->id]);

    $this->actingAs($admin)->post(route('fun-games.close', $event), ['champion_id' => $champion->id])->assertRedirect()->assertSessionHasNoErrors();
    expect($champion->badges()->pluck('slug'))->toContain('champion', $earned)->not->toContain($locked);
    $this->delete(route('fun-games.destroy', $event), ['delete_confirmation' => 'HAPUS'])->assertRedirect()->assertSessionHasNoErrors();
    expect($champion->badges()->pluck('slug'))->toContain('champion')->not->toContain($earned);
})->with([[2, 'champion-2', 'champion-3'], [3, 'champion-3', 'champion-5'], [5, 'champion-5', 'unused-tier']]);

test('double podium counts once per event and cancelled results never unlock champion goals', function () {
    $this->seed(ProductionSeeder::class);
    $member = User::factory()->create();
    $event = FunGame::create(['title' => 'Double', 'event_date' => '2026-09-01', 'status' => FunGameStatus::Completed, 'champion_id' => $member->id]);
    $event->forceFill(['format' => 'double'])->save();
    $event->podiumPlayers()->attach($member, ['position' => 1]);
    FunGame::create(['title' => 'Dibatalkan', 'event_date' => '2026-09-02', 'status' => FunGameStatus::Cancelled, 'champion_id' => $member->id]);

    $member->syncAchievementBadges(['champions']);

    expect($member->badges()->pluck('slug')->all())->toBe(['champion']);
    expect($member->achievementProgress()->first(fn ($row) => $row['badge']->slug === 'champion-2')['current'])->toBe(1);
});

test('four consecutive recorded appearances unlock electric form even with podium-only history', function () {
    $this->seed(ProductionSeeder::class);
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $member = User::factory()->create();
    for ($number = 1; $number <= 3; $number++) {
        FunGame::create(['title' => 'Histori '.$number, 'event_date' => '2026-09-0'.$number, 'status' => FunGameStatus::Completed, 'runner_up_id' => $member->id]);
    }
    $member->syncAchievementBadges(['streak']);
    expect($member->badges()->pluck('slug'))->not->toContain('electric-form');
    $event = FunGame::create(['title' => 'Event keempat', 'event_date' => '2026-09-04', 'status' => FunGameStatus::Open, 'created_by' => $admin->id]);

    $this->actingAs($admin)->post(route('fun-games.close', $event), ['champion_id' => $admin->id, 'runner_up_id' => $member->id])->assertRedirect()->assertSessionHasNoErrors();

    expect($member->badges()->pluck('slug'))->toContain('electric-form')->not->toContain('champion');
});

test('drilling badges use sessions rather than the number of attempts', function (int $sessions, string $earned) {
    $this->seed(ProductionSeeder::class);
    $member = User::factory()->create();
    for ($number = 1; $number < $sessions; $number++) {
        PlayerDrill::create(['user_id' => $member->id, 'drill_name' => 'Latihan', 'success_count' => 1, 'fail_count' => 1, 'practice_date' => '2026-10-01']);
    }

    $this->actingAs($member)->post(route('drilling.store'), ['drill_name' => 'Sesi terakhir', 'success_count' => 8, 'fail_count' => 2])->assertRedirect(route('drilling'))->assertSessionHasNoErrors();

    expect($member->badges()->pluck('slug'))->toContain($earned, 'sharpshooter');
})->with([[10, 'drill-starter'], [50, 'drill-master']]);

test('unrecorded appearances break the streak instead of inferring attendance from incomplete history', function () {
    $this->seed(ProductionSeeder::class);
    $member = User::factory()->create();
    foreach ([1, 2, 3, 4, 5] as $number) {
        FunGame::create(['title' => 'FG '.$number, 'event_date' => '2026-09-0'.$number, 'status' => FunGameStatus::Completed,
            'runner_up_id' => $number === 3 ? null : $member->id]);
    }

    $member->syncAchievementBadges(['streak']);

    expect($member->badges()->pluck('slug'))->not->toContain('electric-form');
    expect($member->achievementProgress()->first(fn ($row) => $row['badge']->slug === 'electric-form')['current'])->toBe(2);
});
