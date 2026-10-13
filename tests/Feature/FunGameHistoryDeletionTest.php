<?php

use App\Enums\FunGameStatus;
use App\Enums\Handicap;
use App\Enums\UserRole;
use App\Models\Badge;
use App\Models\FunGame;
use App\Models\HcHistory;
use App\Models\MatchLog;
use App\Models\User;
use Database\Seeders\ProductionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/** @return array{actor: User, player1: User, player2: User, event: FunGame, match: MatchLog} */
function deletableHistoryFixture(UserRole $role = UserRole::Admin, FunGameStatus $status = FunGameStatus::Completed): array
{
    $actor = User::factory()->create(['role' => $role]);
    $player1 = User::factory()->create(['handicap' => Handicap::ThreeB]);
    $player2 = User::factory()->create(['handicap' => Handicap::ThreeA]);
    $event = FunGame::create(['title' => 'Histori Fun Game', 'event_date' => today(), 'status' => $status, 'champion_id' => $player1->id, 'created_by' => $actor->id]);
    $match = MatchLog::create(['fun_game_id' => $event->id, 'player1_id' => $player1->id, 'player2_id' => $player2->id,
        'player1_score' => null, 'player2_score' => null, 'winner_id' => $player1->id, 'player1_hc' => Handicap::ThreeB, 'player2_hc' => Handicap::ThreeA, 'recorded_by' => $actor->id]);
    $event->podiumPlayers()->attach([$player1->id => ['position' => 1], $player2->id => ['position' => 2]]);

    return compact('actor', 'player1', 'player2', 'event', 'match');
}

test('admins and superadmins can delete archived events with matches and podium while preserving accounts and HC history', function (UserRole $role, FunGameStatus $status) {
    $this->seed(ProductionSeeder::class);
    ['actor' => $actor, 'player1' => $p1, 'player2' => $p2, 'event' => $event, 'match' => $match] = deletableHistoryFixture($role, $status);
    $p1->badges()->attach(Badge::whereIn('slug', ['champion', 'giant-killer', 'sharpshooter'])->pluck('id'));
    $history = HcHistory::create(['user_id' => $p1->id, 'old_hc' => '3SB', 'new_hc' => '3B', 'reason' => 'Evaluasi sebelumnya.', 'changed_by' => $actor->id, 'approved_at' => now()]);
    $account = $p1->fresh()->getAttributes();

    $this->actingAs($actor)->get(route('fun-games.show', $event))->assertOk()->assertSee('Hapus Histori')->assertSee('Ketik HAPUS untuk konfirmasi');
    $this->delete(route('fun-games.destroy', $event), ['delete_confirmation' => 'HAPUS'])->assertRedirect(route('fun-games.index'))->assertSessionHasNoErrors();

    $this->assertDatabaseMissing('fun_games', ['id' => $event->id]);
    $this->assertDatabaseMissing('match_logs', ['id' => $match->id]);
    $this->assertDatabaseMissing('fun_game_podium', ['fun_game_id' => $event->id]);
    $this->assertDatabaseHas('hc_histories', ['id' => $history->id, 'user_id' => $p1->id]);
    $this->assertDatabaseCount('users', 3);
    expect($p1->fresh()->getAttributes())->toBe($account);
    expect($p1->totalMatchesCount())->toBe(0);
    expect($p1->totalWinsCount())->toBe(0);
    expect($p2->totalMatchesCount())->toBe(0);
    expect($p1->funGamesParticipatedCount())->toBe(0);
    expect($p1->badges()->pluck('slug')->all())->toBe(['sharpshooter']);
    $this->get(route('fun-games.show', $event))->assertNotFound();
})->with([UserRole::Admin, UserRole::Superadmin])->with([FunGameStatus::Completed, FunGameStatus::Cancelled]);

test('members cannot delete history or see deletion controls', function () {
    ['actor' => $actor, 'event' => $event, 'match' => $match] = deletableHistoryFixture(UserRole::Member);
    $this->actingAs($actor)->get(route('fun-games.show', $event))->assertOk()->assertDontSee('Hapus Histori')->assertDontSee('Hapus Permanen');
    $this->delete(route('fun-games.destroy', $event), ['delete_confirmation' => 'HAPUS'])->assertForbidden();
    $this->assertDatabaseHas('fun_games', ['id' => $event->id]);
    $this->assertDatabaseHas('match_logs', ['id' => $match->id]);
});

test('an empty cancelled history can be deleted without affecting any players', function () {
    $actor = User::factory()->create(['role' => UserRole::Superadmin]);
    $event = FunGame::create(['title' => 'Event Libur', 'event_date' => today(), 'status' => FunGameStatus::Cancelled, 'created_by' => $actor->id]);
    $this->actingAs($actor)->delete(route('fun-games.destroy', $event), ['delete_confirmation' => 'HAPUS'])->assertRedirect(route('fun-games.index'))->assertSessionHasNoErrors();
    $this->assertDatabaseMissing('fun_games', ['id' => $event->id]);
    $this->assertDatabaseCount('users', 1);
});

test('guests cannot delete fun game history', function () {
    ['event' => $event, 'match' => $match] = deletableHistoryFixture();
    $this->delete(route('fun-games.destroy', $event), ['delete_confirmation' => 'HAPUS'])->assertRedirect(route('login'));
    $this->assertDatabaseHas('match_logs', ['id' => $match->id]);
    $this->assertDatabaseHas('fun_games', ['id' => $event->id]);
});

test('deletion requires explicit confirmation and leaves all records intact otherwise', function (mixed $confirmation) {
    ['actor' => $actor, 'event' => $event, 'match' => $match] = deletableHistoryFixture();
    $this->actingAs($actor)->deleteJson(route('fun-games.destroy', $event), ['delete_confirmation' => $confirmation])->assertUnprocessable()->assertJsonValidationErrors('delete_confirmation');
    $this->assertDatabaseHas('fun_games', ['id' => $event->id]);
    $this->assertDatabaseHas('match_logs', ['id' => $match->id]);
    $this->assertDatabaseCount('fun_game_podium', 2);
})->with(['missing' => [null], 'incorrect' => ['hapus'], 'malformed' => [['HAPUS']]]);

test('an ongoing event cannot be removed through the history deletion endpoint', function () {
    ['actor' => $actor, 'event' => $event, 'match' => $match] = deletableHistoryFixture(UserRole::Superadmin, FunGameStatus::Open);
    $this->actingAs($actor)->get(route('fun-games.show', $event))->assertDontSee('Hapus Histori')->assertDontSee('Hapus Permanen');
    $this->deleteJson(route('fun-games.destroy', $event), ['delete_confirmation' => 'HAPUS'])->assertUnprocessable()->assertJsonValidationErrors(['fun_game' => 'Event masih berjalan. Tutup event sebelum menghapus histori.']);
    $this->assertDatabaseHas('fun_games', ['id' => $event->id]);
    $this->assertDatabaseHas('match_logs', ['id' => $match->id]);
});

test('deleting one event preserves other history and badges still supported by single or double titles', function (string $format, int $individualMatches, bool $giantKillerEligible) {
    $this->seed(ProductionSeeder::class);
    ['actor' => $actor, 'player1' => $p1, 'event' => $event, 'match' => $match] = deletableHistoryFixture();
    $p1->badges()->attach(Badge::whereIn('slug', ['champion', 'giant-killer'])->pluck('id'));
    $remaining = FunGame::create(['title' => 'Histori Lain', 'event_date' => today()->subWeek(), 'status' => FunGameStatus::Completed, 'champion_id' => $format === 'single' ? $p1->id : null, 'created_by' => $actor->id]);
    $remaining->forceFill(['format' => $format])->save();
    $remaining->podiumPlayers()->attach($p1->id, ['position' => 1]);
    $otherMatch = $match->replicate();
    $otherMatch->fun_game_id = $remaining->id;
    $otherMatch->save();
    $original = $otherMatch->fresh()->getAttributes();

    $this->actingAs($actor)->delete(route('fun-games.destroy', $event), ['delete_confirmation' => 'HAPUS'])->assertSessionHasNoErrors();
    $this->assertDatabaseHas('fun_games', ['id' => $remaining->id]);
    expect($otherMatch->fresh()->getAttributes())->toBe($original);
    expect($p1->allMatches()->count())->toBe(1);
    expect($p1->totalMatchesCount())->toBe($individualMatches);
    expect($p1->totalWinsCount())->toBe($individualMatches);
    expect($p1->funGamesParticipatedCount())->toBe(1);
    expect($p1->badges()->where('slug', 'champion')->exists())->toBeTrue();
    expect($p1->badges()->where('slug', 'giant-killer')->exists())->toBe($giantKillerEligible);
})->with(['single' => ['single', 1, true], 'double' => ['double', 0, false]]);

test('deleting a double event updates both champions without touching runner up badges or other titles', function () {
    $this->seed(ProductionSeeder::class);
    ['actor' => $actor, 'player1' => $p1, 'player2' => $p2, 'event' => $event] = deletableHistoryFixture();
    $champion = Badge::where('slug', 'champion')->firstOrFail();
    $runnerUp = User::factory()->create();
    foreach ([$p1, $p2, $runnerUp] as $player) {
        $player->badges()->attach($champion);
    }
    $event->forceFill(['format' => 'double', 'champion_id' => null])->save();
    $event->podiumPlayers()->updateExistingPivot($p2->id, ['position' => 1]);
    $event->podiumPlayers()->attach($runnerUp->id, ['position' => 2]);
    FunGame::create(['title' => 'Juara Lain', 'event_date' => today()->subWeek(), 'status' => FunGameStatus::Completed, 'champion_id' => $p1->id, 'created_by' => $actor->id]);

    $this->actingAs($actor)->delete(route('fun-games.destroy', $event), ['delete_confirmation' => 'HAPUS'])->assertSessionHasNoErrors();
    expect($p1->badges()->whereKey($champion->id)->exists())->toBeTrue();
    expect($p2->badges()->whereKey($champion->id)->exists())->toBeFalse();
    expect($runnerUp->badges()->whereKey($champion->id)->exists())->toBeTrue();
    $this->assertDatabaseMissing('fun_game_podium', ['fun_game_id' => $event->id]);
});

test('event names in the deletion confirmation are escaped', function () {
    ['actor' => $actor, 'event' => $event] = deletableHistoryFixture();
    $event->update(['title' => '<script>alert(1)</script>']);
    $this->actingAs($actor)->get(route('fun-games.show', $event))->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
});

test('deleting a match winners event leaves their unrelated champion badge unchanged', function () {
    $this->seed(ProductionSeeder::class);
    ['actor' => $actor, 'player1' => $p1, 'player2' => $p2, 'event' => $event] = deletableHistoryFixture();
    $p1->badges()->attach(Badge::whereIn('slug', ['champion', 'giant-killer'])->pluck('id'));
    $event->update(['champion_id' => $p2->id]);
    $event->podiumPlayers()->updateExistingPivot($p1->id, ['position' => 2]);
    $event->podiumPlayers()->updateExistingPivot($p2->id, ['position' => 1]);

    $this->actingAs($actor)->delete(route('fun-games.destroy', $event), ['delete_confirmation' => 'HAPUS'])->assertSessionHasNoErrors();
    expect($p1->badges()->where('slug', 'champion')->exists())->toBeTrue();
    expect($p1->badges()->where('slug', 'giant-killer')->exists())->toBeFalse();
});
