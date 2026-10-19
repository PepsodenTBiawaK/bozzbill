<?php

use App\Enums\FunGameStatus;
use App\Enums\Handicap;
use App\Enums\MatchVoor;
use App\Enums\UserRole;
use App\Models\FunGame;
use App\Models\FunGameTeam;
use App\Models\MatchLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/** @return array{admin: User, first: User, second: User, event: FunGame, payload: array<string, mixed>} */
function voorFixture(Handicap $firstHc = Handicap::ThreeB, Handicap $secondHc = Handicap::ThreeN): array
{
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $first = User::factory()->create(['handicap' => $firstHc]);
    $second = User::factory()->create(['handicap' => $secondHc]);
    $event = FunGame::create(['title' => 'Aturan Bozz', 'format' => 'single', 'status' => FunGameStatus::Open, 'event_date' => '2026-10-19']);
    $payload = ['player1_id' => $first->id, 'player2_id' => $second->id, 'winner_id' => $second->id, 'player1_score' => 3, 'player2_score' => 5];

    return compact('admin', 'first', 'second', 'event', 'payload');
}

test('single match recording derives the voor and recipient from historical handicap snapshots', function (Handicap $firstHc, Handicap $secondHc, MatchVoor $voor, int $recipientSide) {
    ['admin' => $admin, 'first' => $first, 'second' => $second, 'event' => $event, 'payload' => $payload] = voorFixture($firstHc, $secondHc);

    $this->actingAs($admin)->post(route('fun-games.matches.store', $event), [
        ...$payload, 'voor_type' => 'seven_up', 'voor_recipient_id' => $admin->id, 'race_to' => 3, 'player1_hc' => '3+',
    ])->assertSessionHasNoErrors();

    $match = MatchLog::sole();
    expect($match->voor_type)->toBe($voor);
    expect($match->voor_recipient_id)->toBe(match ($recipientSide) {
        1 => $first->id, 2 => $second->id, default => null
    });
    expect($match->player1_hc)->toBe($firstHc);
    expect($match->inferredRace())->toBe(5);
    $this->get(route('fun-games.show', $event))->assertOk()->assertSee('Race 5')->assertSee($voor->label());
})->with([
    [Handicap::ThreeN, Handicap::ThreeN, MatchVoor::None, 0],
    [Handicap::ThreeB, Handicap::ThreeN, MatchVoor::OneFrame, 1],
    [Handicap::ThreeA, Handicap::ThreeB, MatchVoor::EightUp, 2],
    [Handicap::ThreeSb, Handicap::ThreeA, MatchVoor::EightConnected, 1],
    [Handicap::ThreePlus, Handicap::ThreeSb, MatchVoor::SevenUp, 2],
]);

test('editing scores preserves the original HC and voor after a participant changes handicap', function () {
    ['admin' => $admin, 'first' => $first, 'event' => $event, 'payload' => $payload] = voorFixture();
    $this->actingAs($admin)->post(route('fun-games.matches.store', $event), $payload)->assertSessionHasNoErrors();
    $match = MatchLog::sole();
    $first->update(['handicap' => Handicap::ThreePlus]);

    $this->put(route('fun-games.matches.update', [$event, $match]), [...$payload, 'player1_score' => 2, 'player2_score' => 4])->assertSessionHasNoErrors();

    expect($match->fresh()->voor_type)->toBe(MatchVoor::OneFrame);
    expect($match->fresh()->voor_recipient_id)->toBe($first->id);
    expect($match->fresh()->player1_hc)->toBe(Handicap::ThreeB);
    expect($match->fresh()->inferredRace())->toBe(4);
});

test('a replacement player receives a freshly calculated voor while unchanged players retain their HC', function () {
    ['admin' => $admin, 'first' => $first, 'event' => $event, 'payload' => $payload] = voorFixture();
    $this->actingAs($admin)->post(route('fun-games.matches.store', $event), $payload);
    $match = MatchLog::sole();
    $replacement = User::factory()->create(['handicap' => Handicap::ThreePlus]);

    $this->put(route('fun-games.matches.update', [$event, $match]), [...$payload, 'player2_id' => $replacement->id, 'winner_id' => $replacement->id])->assertSessionHasNoErrors();

    expect($match->fresh()->voor_type)->toBe(MatchVoor::EightConnected);
    expect($match->fresh()->voor_recipient_id)->toBe($first->id);
    expect($match->fresh()->player2_hc)->toBe(Handicap::ThreePlus);
});

test('race is inferred only from a valid completed score of three to five', function (?int $first, ?int $second, bool $firstWon, ?int $expected) {
    $match = new MatchLog(['player1_id' => 1, 'player2_id' => 2, 'winner_id' => $firstWon ? 1 : 2, 'player1_score' => $first, 'player2_score' => $second]);

    expect($match->inferredRace())->toBe($expected);
})->with([[3, 1, true, 3], [3, 5, false, 5], [2, 4, false, 4], [null, null, true, null], [3, 3, true, null], [2, 1, true, null], [6, 3, true, null], [3, 5, true, null]]);

test('legacy voor remains unknown when old results are corrected without replacing participants', function () {
    ['admin' => $admin, 'event' => $event, 'payload' => $payload] = voorFixture();
    $match = MatchLog::create([...$payload, 'fun_game_id' => $event->id, 'player1_hc' => '3B', 'player2_hc' => '3N']);

    $this->actingAs($admin)->put(route('fun-games.matches.update', [$event, $match]), [...$payload, 'stage' => 'Final'])->assertSessionHasNoErrors();

    expect($match->fresh()->voor_type)->toBeNull();
    $this->get(route('fun-games.show', $event))->assertOk()->assertSee('Voor tidak tercatat');
});

test('double recording has no voor and still infers race from its final score', function () {
    ['admin' => $admin, 'event' => $event] = voorFixture();
    $event->update(['format' => 'double']);
    $first = FunGameTeam::factory()->create(['fun_game_id' => $event->id]);
    $second = FunGameTeam::factory()->create(['fun_game_id' => $event->id]);
    $this->actingAs($admin)->post(route('fun-games.matches.store', $event), ['team1_id' => $first->id, 'team2_id' => $second->id, 'winner_team_id' => $second->id, 'player1_score' => 3, 'player2_score' => 5, 'voor_type' => 'one_frame'])->assertSessionHasNoErrors();
    $match = MatchLog::sole();
    expect($match->voor_type)->toBeNull();
    expect($match->voor_recipient_id)->toBeNull();
    expect($match->inferredRace())->toBe(5);
    $response = $this->get(route('fun-games.quick-logger'))->assertOk()->assertDontSee('x-text="voorPreview"', false);
    $this->get(route('fun-games.show', $event))->assertOk()->assertSee('Race 5')->assertDontSee('Voor tidak tercatat');
});

test('migration rollback refuses to discard recorded voor snapshots', function () {
    ['admin' => $admin, 'event' => $event, 'payload' => $payload] = voorFixture();
    $this->actingAs($admin)->post(route('fun-games.matches.store', $event), $payload)->assertSessionHasNoErrors();
    $migration = require database_path('migrations/2026_10_19_093908_add_voor_snapshot_to_match_logs_table.php');

    expect(fn () => $migration->down())->toThrow(RuntimeException::class, 'catatan voor pertandingan harus tetap dipertahankan');
    $this->assertDatabaseCount('match_logs', 1);
    expect(MatchLog::sole()->voor_type)->toBe(MatchVoor::OneFrame);
});
