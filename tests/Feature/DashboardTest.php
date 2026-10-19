<?php

use App\Enums\UserRole;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guests cannot view the community dashboard', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

test('members cannot view the community dashboard', function () {
    $member = User::factory()->create(['phone' => '081100000001', 'is_active' => true]);

    $this->actingAs($member)->get(route('dashboard'))->assertForbidden();
});

test('the admin dashboard summarizes the community instead of the admin account', function () {
    $this->travelTo(Carbon::parse('2026-10-10 12:00:00'));
    $this->seed();
    $admin = User::where('role', UserRole::Admin)->firstOrFail();

    $response = $this->actingAs($admin)->get(route('dashboard'));

    $response->assertOk()
        ->assertSee('Dashboard Pengurus')
        ->assertViewHas('stats', [
            'active_members' => 7,
            'inactive_members' => 1,
            'total_matches' => 25,
            'completed_events' => 4,
            'drills_this_month' => 4,
        ])
        ->assertViewHas('recommendationCounts', ['promote' => 0, 'demote' => 0, 'watch' => 8, 'stay' => 0])
        ->assertViewHas('handicapDistribution', ['3SB' => 1, '3B' => 1, '3N' => 3, '3A' => 2, '3+' => 1])
        ->assertViewHas('activeEvent', fn ($event): bool => $event->title === 'Fun Game Bozz #13 - Special Edition' && $event->matches_count === 0)
        ->assertViewHas('recentMatches', fn ($matches): bool => $matches->count() === 5 && $matches->every(fn ($match): bool => $match->recorder->is($admin)))
        ->assertSee(route('fun-games.quick-logger'), false)
        ->assertSee(route('evaluating-hc.index'), false);
});

test('the community dashboard handles an empty community', function () {
    $admin = User::factory()->create(['phone' => '081100000001', 'role' => UserRole::Admin, 'is_active' => true]);

    $response = $this->actingAs($admin)->get(route('dashboard'));

    $response->assertOk()
        ->assertViewHas('stats', [
            'active_members' => 1,
            'inactive_members' => 0,
            'total_matches' => 0,
            'completed_events' => 0,
            'drills_this_month' => 0,
        ])
        ->assertViewHas('activeEvent', null)
        ->assertViewHas('recentMatches', fn ($matches): bool => $matches->isEmpty())
        ->assertViewHas('recommendationCounts', ['promote' => 0, 'demote' => 0, 'watch' => 1, 'stay' => 0]);
});

test('dashboard recommendations continue to include players with no recent matches', function () {
    $admin = User::factory()->create(['phone' => '081100000001', 'role' => UserRole::Admin, 'is_active' => true]);
    User::factory()->create(['phone' => '081100000002', 'is_active' => true]);
    User::factory()->create(['phone' => '081100000003', 'is_active' => false]);

    $this->actingAs($admin)->get(route('dashboard'))
        ->assertOk()
        ->assertViewHas('recommendationCounts', ['promote' => 0, 'demote' => 0, 'watch' => 3, 'stay' => 0]);
});
