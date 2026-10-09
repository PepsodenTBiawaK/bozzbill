<?php

use App\Enums\FunGameStatus;
use App\Models\FunGame;
use App\Models\MatchLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
});

test('member can view profile with handicap history timeline', function () {
    $aprPinky = User::where('phone', '081111111111')->first();

    $response = $this->actingAs($aprPinky)->get(route('profile'));

    $response->assertStatus(200);
    $response->assertSee('Profil Anggota');
    $response->assertSee('Apr Pinky');
    $response->assertSee('Timeline Riwayat Handicap (HC)');
    $response->assertSee('HC 3A');
});

test('member can update name', function () {
    $member = User::where('phone', '081111111111')->first();

    $response = $this->actingAs($member)->put(route('profile.update'), [
        'name' => 'Apr Pinky Updated',
    ]);

    $response->assertRedirect(route('profile'));
    $response->assertSessionHas('status_success');
    expect($member->fresh()->name)->toBe('Apr Pinky Updated');
});

test('member can update password with valid current password', function () {
    $member = User::where('phone', '081111111111')->first();

    $response = $this->actingAs($member)->put(route('profile.password'), [
        'current_password' => 'password',
        'password' => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ]);

    $response->assertRedirect(route('profile'));
    $response->assertSessionHas('status_success');
});

test('member can view fun games index with active and completed events', function () {
    $member = User::where('phone', '081111111111')->first();

    $response = $this->actingAs($member)->get(route('fun-games.index'));

    $response->assertStatus(200);
    $response->assertSee('Event Fun Game');
    $response->assertSee('Fun Game Bozz #13 - Special Edition');
    $response->assertSee('Arsip Event Selesai');
});

test('member can view specific fun game detail', function () {
    $member = User::where('phone', '081111111111')->first();
    $event = FunGame::first();

    $response = $this->actingAs($member)->get(route('fun-games.show', $event));

    $response->assertStatus(200);
    $response->assertSee($event->title);
    $response->assertSee('Rekap Seluruh Pertandingan');
});

test('regular member cannot access quick match logger', function () {
    $member = User::where('phone', '081111111111')->first();

    $response = $this->actingAs($member)->get(route('fun-games.quick-logger'));

    $response->assertStatus(403);
});

test('admin can access quick match logger and record a match with scores', function () {
    $admin = User::where('phone', '081234567890')->first();
    $activeEvent = FunGame::where('status', FunGameStatus::Open)->first();
    $p1 = User::where('phone', '081111111111')->first(); // Apr Pinky
    $p2 = User::where('phone', '082222222222')->first(); // Pedong

    $response = $this->actingAs($admin)->get(route('fun-games.quick-logger'));
    $response->assertStatus(200);
    $response->assertSee('Quick Match Logger');

    $postResponse = $this->actingAs($admin)->post(route('fun-games.matches.store', $activeEvent), [
        'player1_id' => $p1->id,
        'player2_id' => $p2->id,
        'player1_score' => 3,
        'player2_score' => 1,
        'winner_id' => $p1->id,
        'stage' => 'Final',
    ]);

    $postResponse->assertSessionHas('status_success');

    $match = MatchLog::where('fun_game_id', $activeEvent->id)
        ->where('player1_id', $p1->id)
        ->where('player2_id', $p2->id)
        ->first();

    expect($match)->not->toBeNull()
        ->and($match->player1_score)->toBe(3)
        ->and($match->player2_score)->toBe(1)
        ->and($match->winner_id)->toBe($p1->id);
});

test('admin can close event and award champion badge', function () {
    $admin = User::where('phone', '081234567890')->first();
    $activeEvent = FunGame::where('status', FunGameStatus::Open)->first();
    $p1 = User::where('phone', '081111111111')->first(); // Apr Pinky

    $response = $this->actingAs($admin)->post(route('fun-games.close', $activeEvent), [
        'champion_id' => $p1->id,
        'runner_up_id' => null,
        'third_place_id' => null,
    ]);

    $response->assertRedirect(route('fun-games.show', $activeEvent));
    $activeEvent->refresh();
    expect($activeEvent->status)->toBe(FunGameStatus::Completed)
        ->and($activeEvent->champion_id)->toBe($p1->id);
});
