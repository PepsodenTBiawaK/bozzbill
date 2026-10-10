<?php

use App\Enums\UserRole;
use App\Models\MatchLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
});

test('guests visiting head to head page are redirected to login', function () {
    $response = $this->get(route('head-to-head'));

    $response->assertRedirect(route('login'));
});

test('authenticated member can view head to head page with accurate duel stats', function () {
    $aprPinky = User::where('phone', '081111111111')->first();
    $pedong = User::where('phone', '082222222222')->first();

    $response = $this->actingAs($aprPinky)->get(route('head-to-head'));

    $response->assertStatus(200);
    $response->assertSee('Head to Head');
    $response->assertSee('Apr Pinky');
    $response->assertSee('Pedong');
    $response->assertSee('VS');
    $response->assertSee('5 Match');
    $response->assertSee('Riwayat Pertemuan');
    $response->assertSee('Babak Penyisihan');
});

test('member can switch opponent via query parameter', function () {
    $aprPinky = User::where('phone', '081111111111')->first();
    $nina = User::where('phone', '084444444444')->first();

    $response = $this->actingAs($aprPinky)->get(route('head-to-head', ['opponent_id' => $nina->id]));

    $response->assertStatus(200);
    $response->assertSee('Apr Pinky');
    $response->assertSee('Nina');
    $response->assertViewHas('selectedPlayer1', fn (User $player): bool => $player->is($aprPinky));
    $response->assertViewHas('selectedOpponent', fn (User $player): bool => $player->is($nina));
});

test('authenticated users can compare two other active players', function (UserRole $role) {
    $viewer = User::where('role', $role)->where('phone', '!=', '081111111111')->where('phone', '!=', '082222222222')->where('is_active', true)->firstOrFail();
    $player1 = User::where('phone', '081111111111')->firstOrFail();
    $player2 = User::where('phone', '082222222222')->firstOrFail();

    $response = $this->actingAs($viewer)->get(route('head-to-head', [
        'player1_id' => $player1->id,
        'opponent_id' => $player2->id,
    ]));

    $response->assertOk();
    $response->assertViewHas('selectedPlayer1', fn (User $player): bool => $player->is($player1));
    $response->assertViewHas('selectedOpponent', fn (User $player): bool => $player->is($player2));
    $response->assertViewHas('h2h', fn (array $stats): bool => $stats['total_matches'] === 5
        && $stats['user_wins'] === 3
        && $stats['opponent_wins'] === 2
        && $stats['user_percentage'] === 60);
    $response->assertSee('Daftar seluruh match resmi antara Apr Pinky dan Pedong');
})->with([
    'member' => UserRole::Member,
    'admin' => UserRole::Admin,
]);

test('swapping players reverses win statistics and displayed match scores', function () {
    $viewer = User::where('phone', '084444444444')->firstOrFail();
    $aprPinky = User::where('phone', '081111111111')->firstOrFail();
    $pedong = User::where('phone', '082222222222')->firstOrFail();
    MatchLog::where('player1_id', $aprPinky->id)
        ->where('player2_id', $pedong->id)
        ->where('winner_id', $aprPinky->id)
        ->firstOrFail()
        ->update(['player1_score' => 17, 'player2_score' => 11]);

    $response = $this->actingAs($viewer)->get(route('head-to-head', [
        'player1_id' => $pedong->id,
        'opponent_id' => $aprPinky->id,
    ]));

    $response->assertOk();
    $response->assertViewHas('h2h', fn (array $stats): bool => $stats['user_wins'] === 2
        && $stats['opponent_wins'] === 3
        && $stats['user_percentage'] === 40
        && $stats['opponent_percentage'] === 60);
    expect(preg_replace('/\s+/', ' ', strip_tags($response->getContent())))->toContain('11 : 17');
});

test('active admins can be selected as players', function () {
    $member = User::where('phone', '081111111111')->firstOrFail();
    $admin = User::where('role', UserRole::Admin)->firstOrFail();

    $response = $this->actingAs($member)->get(route('head-to-head', [
        'player1_id' => $admin->id,
        'opponent_id' => $member->id,
    ]));

    $response->assertOk();
    $response->assertViewHas('selectedPlayer1', fn (User $player): bool => $player->is($admin));
    $response->assertViewHas('h2h', fn (array $stats): bool => $stats['total_matches'] > 0);
});

test('the same player cannot be selected on both sides', function () {
    $member = User::where('phone', '081111111111')->firstOrFail();

    $response = $this->actingAs($member)->from(route('head-to-head'))->get(route('head-to-head', [
        'opponent_id' => $member->id,
    ]));

    $response->assertRedirect(route('head-to-head'));
    $response->assertSessionHasErrors(['opponent_id' => 'Pemain pertama dan pemain kedua harus berbeda.']);
});

test('unactivated players can be selected on either side of a comparison', function (string $field) {
    $member = User::where('phone', '081111111111')->firstOrFail();
    $inactive = User::where('is_active', false)->firstOrFail();

    $response = $this->actingAs($member)->from(route('head-to-head'))->get(route('head-to-head', [
        $field => $inactive->id,
    ]));

    $response->assertOk()->assertSessionHasNoErrors();
    $response->assertViewHas($field === 'player1_id' ? 'selectedPlayer1' : 'selectedOpponent',
        fn (User $player): bool => $player->is($inactive));
})->with(['player1_id', 'opponent_id']);

test('unknown and malformed player identifiers are rejected', function (mixed $identifier, string $message) {
    $member = User::where('phone', '081111111111')->firstOrFail();

    $response = $this->actingAs($member)->getJson(route('head-to-head', ['opponent_id' => $identifier]));

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['opponent_id' => $message]);
})->with([
    'unknown player' => [999999, 'Pemain kedua tidak ditemukan.'],
    'malformed identifier' => ['not-a-player', 'Pilih pemain kedua yang valid.'],
]);

test('a pair without any meetings shows a useful empty history', function () {
    $member = User::where('phone', '081111111111')->firstOrFail();
    $pedong = User::where('phone', '082222222222')->firstOrFail();
    $nina = User::where('phone', '084444444444')->firstOrFail();

    $response = $this->actingAs($member)->get(route('head-to-head', [
        'player1_id' => $pedong->id,
        'opponent_id' => $nina->id,
    ]));

    $response->assertOk();
    $response->assertSee('Belum Ada Riwayat Pertandingan');
    $response->assertSee('Pedong dan Nina belum pernah bertemu');
    $response->assertViewHas('h2h', fn (array $stats): bool => $stats['total_matches'] === 0);
});

test('a community with only one activated account can still compare registered players', function () {
    $member = User::where('phone', '081111111111')->firstOrFail();
    User::where('id', '!=', $member->id)->update(['is_active' => false]);

    $response = $this->actingAs($member)->get(route('head-to-head'));

    $response->assertOk();
    $response->assertViewHas('selectedOpponent', fn (User $player): bool => ! $player->is_active);
    $response->assertViewHas('h2h', fn (array $stats): bool => $stats['total_matches'] > 0);
});
