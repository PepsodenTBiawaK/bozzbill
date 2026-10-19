<?php

use App\Enums\FunGameStatus;
use App\Enums\Handicap;
use App\Enums\UserRole;
use App\Models\Badge;
use App\Models\FunGame;
use App\Models\MatchLog;
use App\Models\User;
use Database\Seeders\ProductionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/** @return array{actor: User, player1: User, player2: User, event: FunGame, match: MatchLog, payload: array} */
function editableMatchFixture(UserRole $role = UserRole::Admin): array
{
    $actor = User::factory()->create(['role' => $role]);
    $player1 = User::factory()->create(['handicap' => Handicap::ThreeB]);
    $player2 = User::factory()->create(['handicap' => Handicap::ThreeA]);
    $event = FunGame::create(['title' => 'Event Aktif', 'event_date' => today(), 'status' => FunGameStatus::Open, 'created_by' => $actor->id]);
    $payload = ['player1_id' => $player1->id, 'player2_id' => $player2->id, 'player1_score' => 3, 'player2_score' => 1, 'winner_id' => $player1->id, 'stage' => 'Penyisihan'];
    $match = MatchLog::create([...$payload, 'fun_game_id' => $event->id, 'player1_hc' => $player1->handicap, 'player2_hc' => $player2->handicap, 'recorded_by' => $actor->id]);

    return compact('actor', 'player1', 'player2', 'event', 'match', 'payload');
}

test('guests cannot view edit forms or modify match records', function () {
    ['event' => $event, 'match' => $match, 'payload' => $payload] = editableMatchFixture();
    $original = $match->fresh()->getAttributes();
    $this->get(route('fun-games.matches.edit', [$event, $match]))->assertRedirect(route('login'));
    $this->put(route('fun-games.matches.update', [$event, $match]), $payload)->assertRedirect(route('login'));
    $this->delete(route('fun-games.matches.destroy', [$event, $match]))->assertRedirect(route('login'));
    expect($match->fresh()->getAttributes())->toBe($original);
});

test('administrators can correct an ongoing match and its derived statistics', function (UserRole $role) {
    $this->seed(ProductionSeeder::class);
    ['actor' => $actor, 'player1' => $p1, 'player2' => $p2, 'event' => $event, 'match' => $match, 'payload' => $payload] = editableMatchFixture($role);
    $badge = Badge::where('slug', 'giant-killer')->firstOrFail();
    $p1->badges()->attach($badge);
    $p1->update(['handicap' => Handicap::ThreePlus]);
    $createdAt = $match->created_at;

    $this->actingAs($actor)->get(route('fun-games.matches.edit', [$event, $match]))->assertOk()->assertSee('Simpan Perubahan');
    $this->put(route('fun-games.matches.update', [$event, $match]), [...$payload, 'winner_id' => $p2->id, 'player1_score' => 1, 'player2_score' => 3, 'stage' => 'Final', 'recorded_by' => $p1->id, 'fun_game_id' => 999])
        ->assertRedirect(route('fun-games.show', $event))->assertSessionHasNoErrors();

    expect($match->fresh()->winner_id)->toBe($p2->id);
    expect($match->fresh()->stage)->toBe('Final');
    expect($match->fresh()->player1_hc)->toBe(Handicap::ThreeB);
    expect($match->fresh()->created_at->equalTo($createdAt))->toBeTrue();
    expect($match->fresh()->recorded_by)->toBe($actor->id);
    expect($match->fresh()->fun_game_id)->toBe($event->id);
    expect($p1->totalWinsCount())->toBe(0);
    expect($p2->totalWinsCount())->toBe(1);
    expect($p1->badges()->whereKey($badge->id)->exists())->toBeFalse();
    expect($p2->badges()->whereKey($badge->id)->exists())->toBeFalse();
})->with([UserRole::Admin, UserRole::Superadmin]);

test('correcting player identities retains snapshots for existing players and captures replacement handicap', function () {
    ['actor' => $actor, 'player1' => $p1, 'player2' => $p2, 'event' => $event, 'match' => $match, 'payload' => $payload] = editableMatchFixture();
    $p2->update(['handicap' => Handicap::ThreeB]);
    $replacement = User::factory()->create(['handicap' => Handicap::ThreeN]);

    $this->actingAs($actor)->put(route('fun-games.matches.update', [$event, $match]), [...$payload, 'player1_id' => $p2->id, 'player2_id' => $replacement->id, 'winner_id' => $replacement->id])->assertSessionHasNoErrors();
    expect($match->fresh()->player1_hc)->toBe(Handicap::ThreeA);
    expect($match->fresh()->player2_hc)->toBe(Handicap::ThreeN);
    expect($p1->totalMatchesCount())->toBe(0);
    expect($replacement->totalMatchesCount())->toBe(1);
});

test('a corrected lower handicap winner receives giant killer', function () {
    $this->seed(ProductionSeeder::class);
    ['actor' => $actor, 'player1' => $p1, 'player2' => $p2, 'event' => $event, 'match' => $match, 'payload' => $payload] = editableMatchFixture();
    $match->update(['winner_id' => $p2->id]);
    $this->actingAs($actor)->put(route('fun-games.matches.update', [$event, $match]), $payload)->assertSessionHasNoErrors();
    expect($p1->badges()->where('slug', 'giant-killer')->exists())->toBeTrue();
});

test('deleting a match adjusts stats and only removes giant killer without other qualifying wins', function (bool $otherWin) {
    $this->seed(ProductionSeeder::class);
    ['actor' => $actor, 'player1' => $p1, 'event' => $event, 'match' => $match] = editableMatchFixture();
    $giant = Badge::where('slug', 'giant-killer')->firstOrFail();
    $champion = Badge::where('slug', 'champion')->firstOrFail();
    $p1->badges()->attach([$giant->id, $champion->id]);
    if ($otherWin) {
        $remaining = $match->replicate();
        $remaining->save();
    }

    $this->actingAs($actor)->from(route('fun-games.quick-logger'))->delete(route('fun-games.matches.destroy', [$event, $match]))->assertRedirect(route('fun-games.quick-logger'))->assertSessionHasNoErrors();
    $this->assertDatabaseMissing('match_logs', ['id' => $match->id]);
    expect($p1->totalMatchesCount())->toBe($otherWin ? 1 : 0);
    expect($p1->badges()->whereKey($giant->id)->exists())->toBe($otherWin);
    expect($p1->badges()->whereKey($champion->id)->exists())->toBeTrue();
})->with([true, false]);

test('members cannot access match corrections or delete a match', function () {
    ['actor' => $actor, 'event' => $event, 'match' => $match, 'payload' => $payload] = editableMatchFixture(UserRole::Member);
    $original = $match->fresh()->getAttributes();
    $this->actingAs($actor)->get(route('fun-games.matches.edit', [$event, $match]))->assertForbidden();
    $this->put(route('fun-games.matches.update', [$event, $match]), $payload)->assertForbidden();
    $this->delete(route('fun-games.matches.destroy', [$event, $match]))->assertForbidden();
    $this->get(route('fun-games.show', $event))->assertDontSee('Edit Match')->assertDontSee('Ya, Hapus Match');
    expect($match->fresh()->getAttributes())->toBe($original);
});

test('closed and cancelled events refuse match edits and deletions from administrators even from a previously open form', function (FunGameStatus $status) {
    ['actor' => $actor, 'event' => $event, 'match' => $match, 'payload' => $payload] = editableMatchFixture(UserRole::Admin);
    $this->actingAs($actor)->get(route('fun-games.matches.edit', [$event, $match]))->assertOk();
    $event->update(['status' => $status]);
    $original = $match->fresh()->getAttributes();
    $this->get(route('fun-games.matches.edit', [$event, $match]))->assertForbidden();
    $response = $this->putJson(route('fun-games.matches.update', [$event, $match]), $payload);
    if ($status === FunGameStatus::Completed) {
        $response->assertForbidden();
    } else {
        $response->assertUnprocessable()->assertJsonValidationErrors('fun_game');
    }
    $this->deleteJson(route('fun-games.matches.destroy', [$event, $match]))->assertUnprocessable()->assertJsonValidationErrors('fun_game');
    expect($match->fresh()->getAttributes())->toBe($original);
    $this->get(route('fun-games.show', $event))->assertDontSee('Edit Match')->assertDontSee('Ya, Hapus Match');
})->with([FunGameStatus::Completed, FunGameStatus::Cancelled]);

test('a match cannot be corrected through another events address', function () {
    ['actor' => $actor, 'event' => $event, 'match' => $match, 'payload' => $payload] = editableMatchFixture();
    $otherEvent = $event->replicate();
    $otherEvent->save();
    $this->actingAs($actor)->get(route('fun-games.matches.edit', [$otherEvent, $match]))->assertNotFound();
    $this->put(route('fun-games.matches.update', [$otherEvent, $match]), $payload)->assertNotFound();
    $this->delete(route('fun-games.matches.destroy', [$otherEvent, $match]))->assertNotFound();
    $this->assertDatabaseHas('match_logs', ['id' => $match->id, 'fun_game_id' => $event->id]);
});

test('invalid corrections leave the match unchanged', function (string $field, mixed $value) {
    ['actor' => $actor, 'event' => $event, 'match' => $match, 'payload' => $payload] = editableMatchFixture();
    $original = $match->fresh()->getAttributes();
    $payload[$field] = $value === 'same' ? $payload['player1_id'] : $value;
    $this->actingAs($actor)->putJson(route('fun-games.matches.update', [$event, $match]), $payload)->assertUnprocessable()->assertJsonValidationErrors($field);
    expect($match->fresh()->getAttributes())->toBe($original);
})->with([
    'duplicate players' => ['player2_id', 'same'],
    'unrelated winner' => ['winner_id', 999999],
    'negative score' => ['player1_score', -1],
    'oversized score' => ['player2_score', 51],
    'noninteger score' => ['player1_score', 1.5],
    'unknown player' => ['player1_id', 999999],
    'malformed identifier' => ['player2_id', [1]],
]);

test('ongoing event and quick logger provide correction controls for administrators', function () {
    ['actor' => $actor, 'event' => $event, 'match' => $match] = editableMatchFixture();
    $this->actingAs($actor)->get(route('fun-games.show', $event))->assertOk()->assertSee(route('fun-games.matches.edit', [$event, $match]), false)->assertSee('Ya, Hapus Match');
    $this->get(route('fun-games.quick-logger'))->assertOk()->assertSee('Edit Match')->assertSee('Ya, Hapus Match');
});
