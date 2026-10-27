<?php

use App\Enums\FunGameStatus;
use App\Enums\Handicap;
use App\Enums\UserRole;
use App\Models\FunGame;
use App\Models\MatchLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;

uses(RefreshDatabase::class);

test('a registered member keeps their match history badges and handicap when activating later', function () {
    $this->seed();
    $admin = User::where('role', UserRole::Admin)->firstOrFail();
    $opponent = User::where('phone', '081111111111')->firstOrFail();
    $event = FunGame::where('status', FunGameStatus::Open)->firstOrFail();

    $this->actingAs($admin)->post(route('members.store'), [
        'name' => 'Anggota Baru',
        'phone' => '089988776655',
        'handicap' => Handicap::ThreeB->value,
    ])->assertRedirect(route('roster', ['tab' => 'roster']))->assertSessionHasNoErrors();

    $member = User::where('phone', '089988776655')->firstOrFail();

    $this->get(route('fun-games.quick-logger'))
        ->assertOk()
        ->assertViewHas('members', fn (Collection $members): bool => $members->contains('id', $member->id));
    $this->get(route('fun-games.show', $event))
        ->assertOk()
        ->assertViewHas('members', fn (Collection $members): bool => $members->contains('id', $member->id));
    $this->get(route('roster', ['tab' => 'roster']))->assertOk()->assertSee('Anggota Baru');

    $this->post(route('fun-games.matches.store', $event), [
        'player1_id' => $member->id,
        'player2_id' => $opponent->id,
        'player1_score' => 3,
        'player2_score' => 1,
        'winner_id' => $member->id,
    ])->assertRedirect()->assertSessionHasNoErrors();
    $this->post(route('fun-games.matches.store', $event), [
        'player1_id' => $opponent->id,
        'player2_id' => $member->id,
        'player1_score' => 3,
        'player2_score' => 2,
        'winner_id' => $opponent->id,
    ])->assertRedirect()->assertSessionHasNoErrors();
    $this->post(route('fun-games.close', $event), [
        'champion_id' => $member->id,
        'runner_up_id' => $opponent->id,
    ])->assertRedirect(route('fun-games.show', $event))->assertSessionHasNoErrors();

    $this->assertDatabaseHas('fun_games', [
        'id' => $event->id,
        'status' => FunGameStatus::Completed->value,
        'champion_id' => $member->id,
    ]);
    $this->get(route('roster.show', $member))->assertOk()->assertViewHas('stats', [
        'win_rate' => 50.0,
        'total_matches' => 2,
        'wins' => 1,
        'total_losses' => 1,
        'fun_games_count' => 1,
    ]);
    $this->get(route('head-to-head', ['player1_id' => $member->id, 'opponent_id' => $opponent->id]))
        ->assertOk()
        ->assertViewHas('h2h', fn (array $stats): bool => $stats['total_matches'] === 2
            && $stats['user_wins'] === 1 && $stats['opponent_wins'] === 1);
    $this->get(route('evaluating-hc.index'))
        ->assertOk()
        ->assertViewHas('evaluations', fn (array $evaluations): bool => collect($evaluations)->contains(
            fn (array $evaluation): bool => $evaluation['user']->is($member) && $evaluation['data']['total_matches'] === 2
        ));
    $this->post(route('evaluating-hc.approve', $member), [
        'decision' => 'promote',
        'new_hc' => Handicap::ThreeN->value,
        'reason' => 'Penyesuaian HC anggota terdaftar.',
    ])->assertRedirect(route('evaluating-hc.index'))->assertSessionHasNoErrors();

    $matchesBeforeActivation = $member->allMatches()->orderBy('id')->get()
        ->map(fn (MatchLog $match): array => $match->getAttributes())->all();
    $handicapHistoryId = $member->hcHistories()->firstOrFail()->id;
    expect($member->fresh()->is_active)->toBeFalse();
    expect($member->fresh()->password)->toBeNull();
    expect($member->badges()->pluck('slug')->sort()->values()->all())->toBe(['champion', 'giant-killer']);

    $this->post(route('logout'))->assertRedirect(route('login'));
    $this->get(route('home'))->assertRedirect(route('login'));
    $this->post(route('login.post'), [
        'phone' => $member->phone,
        'password' => 'member-password',
    ])->assertRedirect(route('login', ['tab' => 'activation', 'phone' => $member->phone]));
    $this->assertGuest();

    $this->post(route('activate.post'), [
        'activation_code' => issueTestActivationCode($member),
        'phone' => $member->phone,
        'password' => 'member-password',
        'password_confirmation' => 'member-password',
    ])->assertRedirect(route('home'))->assertSessionHasNoErrors();

    $this->assertAuthenticatedAs($member);
    expect($member->fresh()->is_active)->toBeTrue();
    expect($member->fresh()->handicap)->toBe(Handicap::ThreeN);
    expect($member->allMatches()->orderBy('id')->get()
        ->map(fn (MatchLog $match): array => $match->getAttributes())->all())->toBe($matchesBeforeActivation);
    expect($member->badges()->pluck('slug')->sort()->values()->all())->toBe(['champion', 'giant-killer']);
    $this->assertDatabaseHas('hc_histories', [
        'id' => $handicapHistoryId,
        'user_id' => $member->id,
        'old_hc' => Handicap::ThreeB->value,
        'new_hc' => Handicap::ThreeN->value,
    ]);
    $this->get(route('home'))
        ->assertOk()
        ->assertViewHas('stats', [
            'win_rate' => 50.0,
            'total_matches' => 2,
            'wins_vs_higher' => 1,
            'fun_games_count' => 1,
        ])
        ->assertViewHas('recentMatches', fn (Collection $matches): bool => $matches->count() === 2);
});

test('a community with only one registered player shows an empty comparison', function () {
    $member = User::factory()->create(['phone' => '081111111111', 'is_active' => true]);

    $this->actingAs($member)->get(route('head-to-head'))
        ->assertOk()
        ->assertSee('Belum ada dua pemain terdaftar untuk dibandingkan.')
        ->assertViewHas('selectedOpponent', null);
});

test('unactivated members can receive the remaining podium positions without activating their account', function (string $field) {
    $this->seed();
    $admin = User::where('role', UserRole::Admin)->firstOrFail();
    $event = FunGame::where('status', FunGameStatus::Open)->firstOrFail();
    $champion = User::where('phone', '081111111111')->firstOrFail();
    $member = User::where('is_active', false)->firstOrFail();

    $this->actingAs($admin)->post(route('fun-games.close', $event), [
        'champion_id' => $champion->id,
        $field => $member->id,
    ])->assertRedirect(route('fun-games.show', $event))->assertSessionHasNoErrors();

    $this->assertDatabaseHas('fun_games', [
        'id' => $event->id,
        'status' => FunGameStatus::Completed->value,
        'champion_id' => $champion->id,
        $field => $member->id,
    ]);
    expect($member->fresh()->is_active)->toBeFalse();
    expect($member->fresh()->password)->toBeNull();
})->with(['runner_up_id', 'third_place_id']);

test('unregistered players still cannot be recorded in a match', function (string $field) {
    $this->seed();
    $admin = User::where('role', UserRole::Admin)->firstOrFail();
    $event = FunGame::where('status', FunGameStatus::Open)->firstOrFail();
    $player = User::where('phone', '081111111111')->firstOrFail();
    $opponent = User::where('phone', '082222222222')->firstOrFail();
    $payload = [
        'player1_id' => $player->id,
        'player2_id' => $opponent->id,
        'player1_score' => 3,
        'player2_score' => 1,
        'winner_id' => $player->id,
    ];
    $payload[$field] = 999999;

    $this->actingAs($admin)->postJson(route('fun-games.matches.store', $event), $payload)
        ->assertUnprocessable()->assertJsonValidationErrors($field);

    $this->assertDatabaseCount('match_logs', 25);
})->with(['player1_id', 'player2_id']);

test('unregistered players still cannot be awarded a podium position', function (string $field) {
    $this->seed();
    $admin = User::where('role', UserRole::Admin)->firstOrFail();
    $event = FunGame::where('status', FunGameStatus::Open)->firstOrFail();
    $player = User::where('phone', '081111111111')->firstOrFail();
    $payload = ['champion_id' => $player->id, $field => 999999];

    $this->actingAs($admin)->postJson(route('fun-games.close', $event), $payload)
        ->assertUnprocessable()->assertJsonValidationErrors($field);

    expect($event->fresh()->status)->toBe(FunGameStatus::Open);
    expect($event->fresh()->champion_id)->toBeNull();
})->with(['champion_id', 'runner_up_id', 'third_place_id']);
