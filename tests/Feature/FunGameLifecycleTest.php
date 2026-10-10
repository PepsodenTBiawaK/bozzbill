<?php

use App\Enums\FunGameStatus;
use App\Enums\UserRole;
use App\Models\FunGame;
use App\Models\MatchLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('members cannot manage fun game events', function (string $routeName) {
    $this->seed();
    $member = User::where('phone', '081111111111')->firstOrFail();
    $event = FunGame::where('status', FunGameStatus::Open)->firstOrFail();
    $parameters = $routeName === 'fun-games.store' ? [] : [$event];

    $this->actingAs($member)->post(route($routeName, $parameters), [])->assertForbidden();

    expect(FunGame::where('status', FunGameStatus::Open)->count())->toBe(1);
    expect(MatchLog::count())->toBe(25);
})->with(['fun-games.store', 'fun-games.matches.store', 'fun-games.close']);

test('an admin cannot open a second event while a fun game is active', function () {
    $this->seed();
    $admin = User::where('role', UserRole::Admin)->firstOrFail();

    $response = $this->actingAs($admin)->post(route('fun-games.store'), [
        'title' => 'Event Kedua',
        'event_date' => '2026-10-10',
    ]);

    $response->assertSessionHasErrors(['title' => 'Masih ada Fun Game aktif. Tutup event tersebut sebelum membuka event baru.']);
    $this->assertDatabaseMissing('fun_games', ['title' => 'Event Kedua']);
    expect(FunGame::where('status', FunGameStatus::Open)->count())->toBe(1);
});

test('an admin can open a new event after closing the active event', function () {
    $this->seed();
    $admin = User::where('role', UserRole::Admin)->firstOrFail();
    $champion = User::where('phone', '081111111111')->firstOrFail();
    $event = FunGame::where('status', FunGameStatus::Open)->firstOrFail();
    $this->actingAs($admin)->post(route('fun-games.close', $event), ['champion_id' => $champion->id])
        ->assertRedirect(route('fun-games.show', $event));

    $response = $this->post(route('fun-games.store'), [
        'title' => 'Fun Game Berikutnya',
        'event_date' => '2026-10-10',
    ]);

    $newEvent = FunGame::where('title', 'Fun Game Berikutnya')->firstOrFail();
    $response->assertRedirect(route('fun-games.show', $newEvent))
        ->assertSessionHasNoErrors();
    expect(FunGame::where('status', FunGameStatus::Open)->count())->toBe(1);
    expect($event->fresh()->status)->toBe(FunGameStatus::Completed);
    expect($newEvent->created_by)->toBe($admin->id);
});

test('an admin cannot add a match to a completed event', function () {
    $this->seed();
    $admin = User::where('role', UserRole::Admin)->firstOrFail();
    $playerOne = User::where('phone', '081111111111')->firstOrFail();
    $playerTwo = User::where('phone', '082222222222')->firstOrFail();
    $event = FunGame::where('status', FunGameStatus::Completed)->firstOrFail();

    $response = $this->actingAs($admin)->post(route('fun-games.matches.store', $event), [
        'player1_id' => $playerOne->id,
        'player2_id' => $playerTwo->id,
        'player1_score' => 3,
        'player2_score' => 1,
        'winner_id' => $playerOne->id,
    ]);

    $response->assertSessionHasErrors(['fun_game' => 'Event sudah ditutup. Hasil pertandingan tidak dapat ditambahkan.']);
    expect(MatchLog::count())->toBe(25);
});

test('a completed event cannot have its champion overwritten by closing it again', function () {
    $this->seed();
    $admin = User::where('role', UserRole::Admin)->firstOrFail();
    $player = User::where('phone', '081111111111')->firstOrFail();
    $event = FunGame::where('status', FunGameStatus::Completed)->firstOrFail();
    $originalChampionId = $event->champion_id;

    $response = $this->actingAs($admin)->post(route('fun-games.close', $event), ['champion_id' => $player->id]);

    $response->assertSessionHasErrors('fun_game');
    expect($event->fresh()->champion_id)->toBe($originalChampionId);
});

test('recording a match without an optional stage uses the default stage', function () {
    $this->seed();
    $admin = User::where('role', UserRole::Admin)->firstOrFail();
    $playerOne = User::where('phone', '081111111111')->firstOrFail();
    $playerTwo = User::where('phone', '082222222222')->firstOrFail();
    $event = FunGame::where('status', FunGameStatus::Open)->firstOrFail();

    $response = $this->actingAs($admin)->post(route('fun-games.matches.store', $event), [
        'player1_id' => $playerOne->id,
        'player2_id' => $playerTwo->id,
        'player1_score' => 3,
        'player2_score' => 1,
        'winner_id' => $playerOne->id,
    ]);

    $response->assertSessionHasNoErrors();
    $this->assertDatabaseHas('match_logs', [
        'fun_game_id' => $event->id,
        'stage' => 'Penyisihan',
        'winner_id' => $playerOne->id,
        'recorded_by' => $admin->id,
    ]);
});

test('inactive members cannot be recorded as match participants', function () {
    $this->seed();
    $admin = User::where('role', UserRole::Admin)->firstOrFail();
    $inactiveMember = User::where('is_active', false)->firstOrFail();
    $player = User::where('phone', '081111111111')->firstOrFail();
    $event = FunGame::where('status', FunGameStatus::Open)->firstOrFail();

    $response = $this->actingAs($admin)->post(route('fun-games.matches.store', $event), [
        'player1_id' => $inactiveMember->id,
        'player2_id' => $player->id,
        'player1_score' => 3,
        'player2_score' => 1,
        'winner_id' => $inactiveMember->id,
    ]);

    $response->assertSessionHasErrors('player1_id');
    expect(MatchLog::count())->toBe(25);
});

test('podium positions must belong to different players', function () {
    $this->seed();
    $admin = User::where('role', UserRole::Admin)->firstOrFail();
    $player = User::where('phone', '081111111111')->firstOrFail();
    $event = FunGame::where('status', FunGameStatus::Open)->firstOrFail();

    $response = $this->actingAs($admin)->post(route('fun-games.close', $event), [
        'champion_id' => $player->id,
        'runner_up_id' => $player->id,
    ]);

    $response->assertSessionHasErrors('runner_up_id');
    expect($event->fresh()->status)->toBe(FunGameStatus::Open);
});

test('match participants must be different even when their identifiers have different representations', function (string $format) {
    $this->seed();
    $admin = User::where('role', UserRole::Admin)->firstOrFail();
    $player = User::where('phone', '081111111111')->firstOrFail();
    $event = FunGame::where('status', FunGameStatus::Open)->firstOrFail();
    $playerOneId = $format === 'mixed' ? $player->id : (string) $player->id;
    $playerTwoId = $format === 'mixed' ? (string) $player->id : '+'.$player->id;

    $response = $this->actingAs($admin)->postJson(route('fun-games.matches.store', $event), [
        'player1_id' => $playerOneId,
        'player2_id' => $playerTwoId,
        'player1_score' => 3,
        'player2_score' => 1,
        'winner_id' => $player->id,
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['player2_id' => 'Pemain 1 dan Pemain 2 tidak boleh sama.']);
    $this->assertDatabaseCount('match_logs', 25);
})->with([
    'integer and numeric string' => 'mixed',
    'equivalent numeric strings' => 'strings',
]);

test('podium identifiers are compared by player identity rather than their representation', function (string $originalPosition, string $duplicatePosition, string $format, string $message) {
    $this->seed();
    $admin = User::where('role', UserRole::Admin)->firstOrFail();
    $champion = User::where('phone', '081111111111')->firstOrFail();
    $runnerUp = User::where('phone', '082222222222')->firstOrFail();
    $thirdPlace = User::where('phone', '084444444444')->firstOrFail();
    $event = FunGame::where('status', FunGameStatus::Open)->firstOrFail();
    $podium = [
        'champion_id' => $champion->id,
        'runner_up_id' => $runnerUp->id,
        'third_place_id' => $thirdPlace->id,
    ];
    $playerId = $podium[$originalPosition];
    $podium[$originalPosition] = $format === 'mixed' ? $playerId : (string) $playerId;
    $podium[$duplicatePosition] = $format === 'mixed' ? (string) $playerId : '+'.$playerId;

    $response = $this->actingAs($admin)->postJson(route('fun-games.close', $event), $podium);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors([$duplicatePosition => $message]);
    expect($event->fresh()->status)->toBe(FunGameStatus::Open);
    expect($event->fresh()->champion_id)->toBeNull();
})->with([
    'runner up matches champion with mixed types' => ['champion_id', 'runner_up_id', 'mixed', 'The runner up id field and champion id must be different.'],
    'runner up matches champion with numeric strings' => ['champion_id', 'runner_up_id', 'strings', 'The runner up id field and champion id must be different.'],
    'third place matches champion with mixed types' => ['champion_id', 'third_place_id', 'mixed', 'The third place id field and champion id must be different.'],
    'third place matches champion with numeric strings' => ['champion_id', 'third_place_id', 'strings', 'The third place id field and champion id must be different.'],
    'third place matches runner up with mixed types' => ['runner_up_id', 'third_place_id', 'mixed', 'The third place id field and runner up id must be different.'],
    'third place matches runner up with numeric strings' => ['runner_up_id', 'third_place_id', 'strings', 'The third place id field and runner up id must be different.'],
]);

test('numeric string winner identifiers preserve the winner score and giant killer award', function () {
    $this->seed();
    $admin = User::where('role', UserRole::Admin)->firstOrFail();
    $winner = User::where('phone', '083333333333')->firstOrFail();
    $opponent = User::where('phone', '081111111111')->firstOrFail();
    $event = FunGame::where('status', FunGameStatus::Open)->firstOrFail();

    $response = $this->actingAs($admin)->post(route('fun-games.matches.store', $event), [
        'player1_id' => (string) $winner->id,
        'player2_id' => (string) $opponent->id,
        'player1_score' => '3',
        'player2_score' => '1',
        'winner_id' => (string) $winner->id,
    ]);

    $response->assertSessionHasNoErrors()
        ->assertSessionHas('status_success', 'Match berhasil dicatat: Fadil menang (3 - 1)!');
    $this->assertDatabaseHas('match_logs', [
        'fun_game_id' => $event->id,
        'player1_id' => $winner->id,
        'player2_id' => $opponent->id,
        'winner_id' => $winner->id,
    ]);
    expect($winner->badges()->where('slug', 'giant-killer')->exists())->toBeTrue();
});

test('a higher handicap winner is not awarded giant killer when form identifiers are strings', function () {
    $this->seed();
    $admin = User::where('role', UserRole::Admin)->firstOrFail();
    $winner = User::where('phone', '081111111111')->firstOrFail();
    $opponent = User::where('phone', '083333333333')->firstOrFail();
    $event = FunGame::where('status', FunGameStatus::Open)->firstOrFail();

    $response = $this->actingAs($admin)->post(route('fun-games.matches.store', $event), [
        'player1_id' => (string) $winner->id,
        'player2_id' => (string) $opponent->id,
        'player1_score' => '3',
        'player2_score' => '1',
        'winner_id' => (string) $winner->id,
    ]);

    $response->assertSessionHasNoErrors()
        ->assertSessionHas('status_success', 'Match berhasil dicatat: Apr Pinky menang (3 - 1)!');
    $this->assertDatabaseHas('match_logs', [
        'fun_game_id' => $event->id,
        'player1_id' => $winner->id,
        'player2_id' => $opponent->id,
        'winner_id' => $winner->id,
    ]);
    expect($winner->badges()->where('slug', 'giant-killer')->exists())->toBeFalse();
});

test('a match winner must be one of the two participants', function () {
    $this->seed();
    $admin = User::where('role', UserRole::Admin)->firstOrFail();
    $playerOne = User::where('phone', '081111111111')->firstOrFail();
    $playerTwo = User::where('phone', '082222222222')->firstOrFail();
    $otherPlayer = User::where('phone', '084444444444')->firstOrFail();
    $event = FunGame::where('status', FunGameStatus::Open)->firstOrFail();

    $response = $this->actingAs($admin)->postJson(route('fun-games.matches.store', $event), [
        'player1_id' => $playerOne->id,
        'player2_id' => $playerTwo->id,
        'player1_score' => 3,
        'player2_score' => 1,
        'winner_id' => $otherPlayer->id,
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['winner_id' => 'Pemenang harus salah satu dari Pemain 1 atau Pemain 2.']);
    $this->assertDatabaseCount('match_logs', 25);
});

test('match identifiers must be scalar integers', function (string $field) {
    $this->seed();
    $admin = User::where('role', UserRole::Admin)->firstOrFail();
    $playerOne = User::where('phone', '081111111111')->firstOrFail();
    $playerTwo = User::where('phone', '082222222222')->firstOrFail();
    $event = FunGame::where('status', FunGameStatus::Open)->firstOrFail();
    $payload = [
        'player1_id' => $playerOne->id,
        'player2_id' => $playerTwo->id,
        'player1_score' => 3,
        'player2_score' => 1,
        'winner_id' => $playerOne->id,
    ];
    $payload[$field] = [$payload[$field]];

    $response = $this->actingAs($admin)->postJson(route('fun-games.matches.store', $event), $payload);

    $response->assertUnprocessable()->assertJsonValidationErrors($field);
    $this->assertDatabaseCount('match_logs', 25);
})->with(['player1_id', 'player2_id', 'winner_id']);

test('podium identifiers must be scalar integers', function (string $field) {
    $this->seed();
    $admin = User::where('role', UserRole::Admin)->firstOrFail();
    $champion = User::where('phone', '081111111111')->firstOrFail();
    $runnerUp = User::where('phone', '082222222222')->firstOrFail();
    $thirdPlace = User::where('phone', '084444444444')->firstOrFail();
    $event = FunGame::where('status', FunGameStatus::Open)->firstOrFail();
    $payload = [
        'champion_id' => $champion->id,
        'runner_up_id' => $runnerUp->id,
        'third_place_id' => $thirdPlace->id,
    ];
    $payload[$field] = [$payload[$field]];

    $response = $this->actingAs($admin)->postJson(route('fun-games.close', $event), $payload);

    $response->assertUnprocessable()->assertJsonValidationErrors($field);
    expect($event->fresh()->status)->toBe(FunGameStatus::Open);
    expect($event->fresh()->champion_id)->toBeNull();
})->with(['champion_id', 'runner_up_id', 'third_place_id']);
