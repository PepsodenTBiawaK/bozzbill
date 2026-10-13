<?php

use App\Enums\FunGameStatus;
use App\Enums\UserRole;
use App\Models\FunGame;
use App\Models\FunGameTeam;
use App\Models\MatchLog;
use App\Models\User;
use Database\Seeders\ProductionSeeder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/** @return array{actor: User, event: FunGame, players: Collection, team1: FunGameTeam, team2: FunGameTeam, payload: array<string, mixed>} */
function doubleFunGameFixture(UserRole $role = UserRole::Admin): array
{
    $actor = User::factory()->create(['role' => $role]);
    $event = FunGame::create(['title' => 'Double Bozz', 'event_date' => '2026-10-13', 'format' => 'double', 'status' => FunGameStatus::Open, 'created_by' => $actor->id]);
    $players = User::factory()->count(4)->sequence(['name' => 'Andi'], ['name' => 'Budi'], ['name' => 'Candra'], ['name' => 'Dedi'])->create();
    $team1 = FunGameTeam::create(['fun_game_id' => $event->id, 'player1_id' => $players[0]->id, 'player2_id' => $players[1]->id]);
    $team2 = FunGameTeam::create(['fun_game_id' => $event->id, 'player1_id' => $players[2]->id, 'player2_id' => $players[3]->id]);
    $payload = ['team1_id' => $team1->id, 'team2_id' => $team2->id, 'winner_team_id' => $team1->id, 'player1_score' => 3, 'player2_score' => 1, 'stage' => 'Penyisihan'];

    return compact('actor', 'event', 'players', 'team1', 'team2', 'payload');
}

/** @param array{actor: User, event: FunGame, payload: array<string, mixed>} $fixture */
function doubleFunGameMatch(array $fixture): MatchLog
{
    return MatchLog::create([...$fixture['payload'], 'fun_game_id' => $fixture['event']->id, 'recorded_by' => $fixture['actor']->id]);
}

test('event creation accepts double and single formats and defaults to single', function (?string $format, string $expected) {
    $actor = User::factory()->create(['role' => UserRole::Admin]);
    $payload = ['title' => 'Fun Game Format', 'event_date' => '2026-10-13'];
    if ($format !== null) {
        $payload['format'] = $format;
    }

    $response = $this->actingAs($actor)->post(route('fun-games.store'), $payload);

    $event = FunGame::where('title', 'Fun Game Format')->firstOrFail();
    $response->assertRedirect(route('fun-games.show', $event))->assertSessionHasNoErrors();
    $this->assertDatabaseHas('fun_games', ['id' => $event->id, 'format' => $expected, 'status' => 'open']);
})->with(['double' => ['double', 'double'], 'single' => ['single', 'single'], 'default' => [null, 'single']]);

test('unsupported event formats return 422 without opening an event', function () {
    $actor = User::factory()->create(['role' => UserRole::Admin]);

    $this->actingAs($actor)->postJson(route('fun-games.store'), ['title' => 'Mixed', 'event_date' => '2026-10-13', 'format' => 'mixed'])->assertUnprocessable()->assertJsonValidationErrors('format');

    $this->assertDatabaseCount('fun_games', 0);
});

test('administrators add correct and remove pairs before they have played', function (UserRole $role) {
    ['actor' => $actor, 'event' => $event] = doubleFunGameFixture($role);
    $players = User::factory()->count(3)->create();

    $this->actingAs($actor)->from(route('fun-games.show', $event))->post(route('fun-games.teams.store', $event), ['player1_id' => $players[0]->id, 'player2_id' => $players[1]->id])->assertRedirect(route('fun-games.show', $event))->assertSessionHasNoErrors();

    $team = FunGameTeam::where('player1_id', $players[0]->id)->firstOrFail();
    $this->assertDatabaseHas('fun_game_teams', ['id' => $team->id, 'fun_game_id' => $event->id, 'player2_id' => $players[1]->id]);

    $this->put(route('fun-games.teams.update', [$event, $team]), ['player1_id' => $players[0]->id, 'player2_id' => $players[2]->id])->assertSessionHasNoErrors();

    $this->assertDatabaseHas('fun_game_teams', ['id' => $team->id, 'player2_id' => $players[2]->id]);

    $this->delete(route('fun-games.teams.destroy', [$event, $team]))->assertSessionHasNoErrors();

    $this->assertModelMissing($team);
})->with([UserRole::Admin, UserRole::Superadmin]);

test('pair validation returns 422 and prevents duplicate or invalid participants', function (string $case, string $field) {
    ['actor' => $actor, 'event' => $event, 'players' => $players] = doubleFunGameFixture();
    $newPlayer = User::factory()->create();
    $payload = match ($case) {
        'same' => ['player1_id' => $newPlayer->id, 'player2_id' => $newPlayer->id],
        'already-first' => ['player1_id' => $players[0]->id, 'player2_id' => $newPlayer->id],
        'already-second' => ['player1_id' => $newPlayer->id, 'player2_id' => $players[1]->id],
        'second-in-first' => ['player1_id' => $players[1]->id, 'player2_id' => $newPlayer->id],
        'first-in-second' => ['player1_id' => $newPlayer->id, 'player2_id' => $players[0]->id],
        'unknown' => ['player1_id' => 999999, 'player2_id' => $newPlayer->id],
        'missing' => ['player1_id' => $newPlayer->id],
        'malformed' => ['player1_id' => [$newPlayer->id], 'player2_id' => $newPlayer->id],
    };

    $this->actingAs($actor)->postJson(route('fun-games.teams.store', $event), $payload)->assertUnprocessable()->assertJsonValidationErrors($field);

    $this->assertDatabaseCount('fun_game_teams', 2);
})->with([
    'same member' => ['same', 'player2_id'], 'existing first partner' => ['already-first', 'player1_id'],
    'existing second partner' => ['already-second', 'player2_id'], 'unknown member' => ['unknown', 'player1_id'],
    'second partner cannot enter first slot' => ['second-in-first', 'player1_id'], 'first partner cannot enter second slot' => ['first-in-second', 'player2_id'],
    'missing partner' => ['missing', 'player2_id'], 'malformed identifier' => ['malformed', 'player1_id'],
]);

test('pair corrections cannot steal a member from another pair', function () {
    ['actor' => $actor, 'event' => $event, 'team1' => $team, 'players' => $players] = doubleFunGameFixture();
    $original = $team->fresh()->getAttributes();

    $this->actingAs($actor)->putJson(route('fun-games.teams.update', [$event, $team]), ['player1_id' => $players[0]->id, 'player2_id' => $players[2]->id])->assertUnprocessable()->assertJsonValidationErrors('player2_id');

    expect($team->fresh()->getAttributes())->toBe($original);
});

test('played pairs cannot be corrected or deleted while their match exists', function () {
    $fixture = doubleFunGameFixture();
    ['actor' => $actor, 'event' => $event, 'team1' => $team] = $fixture;
    $match = doubleFunGameMatch($fixture);
    $replacement = User::factory()->create();
    $original = $team->fresh()->getAttributes();

    $this->actingAs($actor)->putJson(route('fun-games.teams.update', [$event, $team]), ['player1_id' => $team->player1_id, 'player2_id' => $replacement->id])->assertUnprocessable()->assertJsonValidationErrors('team');
    $this->deleteJson(route('fun-games.teams.destroy', [$event, $team]))->assertUnprocessable()->assertJsonValidationErrors('team');

    expect($team->fresh()->getAttributes())->toBe($original);
    $this->assertModelExists($match);
});

test('pair management returns 422 for single or closed events', function (string $format, FunGameStatus $status) {
    ['actor' => $actor, 'event' => $event, 'team1' => $team] = doubleFunGameFixture();
    $event->forceFill(['format' => $format, 'status' => $status])->save();
    $players = User::factory()->count(2)->create();

    $this->actingAs($actor)->postJson(route('fun-games.teams.store', $event), ['player1_id' => $players[0]->id, 'player2_id' => $players[1]->id])->assertUnprocessable()->assertJsonValidationErrors('fun_game');
    $this->putJson(route('fun-games.teams.update', [$event, $team]), ['player1_id' => $players[0]->id, 'player2_id' => $players[1]->id])->assertUnprocessable()->assertJsonValidationErrors('fun_game');
    $this->deleteJson(route('fun-games.teams.destroy', [$event, $team]))->assertUnprocessable()->assertJsonValidationErrors('fun_game');

    $this->assertDatabaseCount('fun_game_teams', 2);
    $this->assertDatabaseHas('fun_game_teams', ['id' => $team->id, 'player1_id' => $team->player1_id, 'player2_id' => $team->player2_id]);
})->with(['single' => ['single', FunGameStatus::Open], 'completed' => ['double', FunGameStatus::Completed], 'cancelled' => ['double', FunGameStatus::Cancelled]]);

test('pairs cannot be managed through another events address', function () {
    ['actor' => $actor, 'event' => $event, 'team1' => $team] = doubleFunGameFixture();
    $other = FunGame::create(['title' => 'Other', 'event_date' => '2026-10-01', 'format' => 'double', 'status' => FunGameStatus::Completed]);

    $this->actingAs($actor)->put(route('fun-games.teams.update', [$other, $team]), ['player1_id' => $team->player1_id, 'player2_id' => $team->player2_id])->assertNotFound();
    $this->delete(route('fun-games.teams.destroy', [$other, $team]))->assertNotFound();

    $this->assertDatabaseHas('fun_game_teams', ['id' => $team->id, 'fun_game_id' => $event->id]);
});

test('administrators record one double match with team identities and can correct and delete it', function (UserRole $role) {
    ['actor' => $actor, 'event' => $event, 'team1' => $team1, 'team2' => $team2, 'payload' => $payload] = doubleFunGameFixture($role);

    $this->actingAs($actor)->from(route('fun-games.quick-logger'))->post(route('fun-games.matches.store', $event), [...$payload, 'player1_id' => $team1->player1_id, 'player2_id' => $team2->player1_id, 'winner_id' => $team1->player1_id, 'player1_hc' => '3A', 'recorded_by' => $team2->player1_id])->assertRedirect(route('fun-games.quick-logger'))->assertSessionHasNoErrors();

    $match = $event->matches()->sole();
    $this->assertDatabaseHas('match_logs', [
        'id' => $match->id, 'team1_id' => $team1->id, 'team2_id' => $team2->id, 'winner_team_id' => $team1->id,
        'player1_id' => null, 'player2_id' => null, 'winner_id' => null, 'player1_hc' => null, 'player2_hc' => null, 'recorded_by' => $actor->id,
    ]);

    $this->put(route('fun-games.matches.update', [$event, $match]), [...$payload, 'winner_team_id' => $team2->id, 'player1_score' => 2, 'player2_score' => 4, 'stage' => 'Final'])->assertRedirect(route('fun-games.show', $event))->assertSessionHasNoErrors();

    $this->assertDatabaseHas('match_logs', ['id' => $match->id, 'winner_team_id' => $team2->id, 'player1_score' => 2, 'player2_score' => 4, 'stage' => 'Final']);

    $this->from(route('fun-games.show', $event))->delete(route('fun-games.matches.destroy', [$event, $match]))->assertSessionHasNoErrors();

    $this->assertModelMissing($match);
})->with([UserRole::Admin, UserRole::Superadmin]);

test('correcting a double matchup locks the replacement pair and releases a pair with no remaining matches', function () {
    $fixture = doubleFunGameFixture();
    ['actor' => $actor, 'event' => $event, 'team1' => $originalTeam, 'payload' => $payload] = $fixture;
    $match = doubleFunGameMatch($fixture);
    $players = User::factory()->count(2)->create();
    $replacement = FunGameTeam::create(['fun_game_id' => $event->id, 'player1_id' => $players[0]->id, 'player2_id' => $players[1]->id]);

    $this->actingAs($actor)->put(route('fun-games.matches.update', [$event, $match]), [...$payload, 'team1_id' => $replacement->id, 'winner_team_id' => $replacement->id])->assertSessionHasNoErrors();

    $this->assertDatabaseHas('match_logs', ['id' => $match->id, 'team1_id' => $replacement->id, 'winner_team_id' => $replacement->id]);
    expect($players[0]->allMatches()->count())->toBe(1);

    $this->deleteJson(route('fun-games.teams.destroy', [$event, $replacement]))->assertUnprocessable()->assertJsonValidationErrors('team');
    $this->delete(route('fun-games.teams.destroy', [$event, $originalTeam]))->assertSessionHasNoErrors();

    $this->assertModelExists($replacement);
    $this->assertModelMissing($originalTeam);
    $this->assertModelExists($match);
});

test('members can form new pairs in a new event after playing with other partners in an earlier event', function () {
    ['actor' => $actor, 'event' => $oldEvent, 'players' => $players] = doubleFunGameFixture();
    $oldEvent->update(['status' => FunGameStatus::Completed]);
    $event = FunGame::create(['title' => 'Pasangan Baru', 'event_date' => '2026-10-14', 'format' => 'double', 'status' => FunGameStatus::Open]);

    $this->actingAs($actor)->post(route('fun-games.teams.store', $event), ['player1_id' => $players[0]->id, 'player2_id' => $players[2]->id])->assertSessionHasNoErrors();

    $this->assertDatabaseHas('fun_game_teams', ['fun_game_id' => $event->id, 'player1_id' => $players[0]->id, 'player2_id' => $players[2]->id]);
    $this->assertDatabaseCount('fun_game_teams', 3);
});

test('double match validation returns 422 and leaves history unchanged', function (string $field, mixed $value, string $errorField) {
    ['actor' => $actor, 'event' => $event, 'team1' => $team1, 'team2' => $team2, 'payload' => $payload] = doubleFunGameFixture();
    $payload[$field] = match ($value) {
        'same-team' => $team1->id, 'losing-team' => $team2->id, default => $value
    };

    $this->actingAs($actor)->postJson(route('fun-games.matches.store', $event), $payload)->assertUnprocessable()->assertJsonValidationErrors($errorField);

    $this->assertDatabaseCount('match_logs', 0);
})->with([
    'same opponents' => ['team2_id', 'same-team', 'team2_id'], 'unknown team' => ['team1_id', 999999, 'team1_id'],
    'unrelated winner' => ['winner_team_id', 999999, 'winner_team_id'], 'winner disagrees with score' => ['winner_team_id', 'losing-team', 'winner_team_id'],
    'tie' => ['player2_score', 3, 'player2_score'], 'negative score' => ['player1_score', -1, 'player1_score'],
    'fractional score' => ['player1_score', 1.5, 'player1_score'], 'oversized score' => ['player2_score', 51, 'player2_score'], 'malformed team' => ['team2_id', [1], 'team2_id'],
]);

test('a team from another event is refused for match recording and corrections', function () {
    $fixture = doubleFunGameFixture();
    ['actor' => $actor, 'event' => $event, 'payload' => $payload] = $fixture;
    $match = doubleFunGameMatch($fixture);
    $other = FunGame::create(['title' => 'Other', 'event_date' => '2026-10-01', 'format' => 'double', 'status' => FunGameStatus::Completed]);
    $players = User::factory()->count(2)->create();
    $foreignTeam = FunGameTeam::create(['fun_game_id' => $other->id, 'player1_id' => $players[0]->id, 'player2_id' => $players[1]->id]);
    $payload['team1_id'] = $foreignTeam->id;
    $payload['winner_team_id'] = $foreignTeam->id;
    $original = $match->fresh()->getAttributes();

    $this->actingAs($actor)->postJson(route('fun-games.matches.store', $event), $payload)->assertUnprocessable()->assertJsonValidationErrors('team1_id');
    $this->putJson(route('fun-games.matches.update', [$event, $match]), $payload)->assertUnprocessable()->assertJsonValidationErrors('team1_id');

    expect($match->fresh()->getAttributes())->toBe($original);
    $this->assertDatabaseCount('match_logs', 1);
});

test('closed double events reject new matches and stale corrections or deletions', function (FunGameStatus $status) {
    $fixture = doubleFunGameFixture();
    ['actor' => $actor, 'event' => $event, 'payload' => $payload] = $fixture;
    $match = doubleFunGameMatch($fixture);
    $event->update(['status' => $status]);
    $original = $match->fresh()->getAttributes();

    $this->actingAs($actor)->postJson(route('fun-games.matches.store', $event), $payload)->assertUnprocessable()->assertJsonValidationErrors('fun_game');
    $this->putJson(route('fun-games.matches.update', [$event, $match]), $payload)->assertUnprocessable()->assertJsonValidationErrors('fun_game');
    $this->deleteJson(route('fun-games.matches.destroy', [$event, $match]))->assertUnprocessable()->assertJsonValidationErrors('fun_game');

    expect($match->fresh()->getAttributes())->toBe($original);
})->with([FunGameStatus::Completed, FunGameStatus::Cancelled]);

test('guests must sign in before managing pairs', function () {
    ['event' => $event, 'team1' => $team] = doubleFunGameFixture();

    $this->post(route('fun-games.teams.store', $event), [])->assertRedirect(route('login'));
    $this->put(route('fun-games.teams.update', [$event, $team]), [])->assertRedirect(route('login'));
    $this->delete(route('fun-games.teams.destroy', [$event, $team]))->assertRedirect(route('login'));

    $this->assertDatabaseCount('fun_game_teams', 2);
});

test('ordinary members cannot manage pairs double scores or close double events', function () {
    $fixture = doubleFunGameFixture(UserRole::Member);
    ['actor' => $actor, 'event' => $event, 'team1' => $team, 'payload' => $payload] = $fixture;
    $match = doubleFunGameMatch($fixture);

    $this->actingAs($actor)->post(route('fun-games.teams.store', $event), [])->assertForbidden();
    $this->put(route('fun-games.teams.update', [$event, $team]), [])->assertForbidden();
    $this->delete(route('fun-games.teams.destroy', [$event, $team]))->assertForbidden();
    $this->post(route('fun-games.matches.store', $event), $payload)->assertForbidden();
    $this->put(route('fun-games.matches.update', [$event, $match]), $payload)->assertForbidden();
    $this->delete(route('fun-games.matches.destroy', [$event, $match]))->assertForbidden();
    $this->post(route('fun-games.close', $event), ['champion_team_id' => $team->id])->assertForbidden();

    $this->assertDatabaseCount('fun_game_teams', 2);
    $this->assertModelExists($match);
    $this->assertDatabaseHas('fun_games', ['id' => $event->id, 'status' => 'open']);
    $this->assertDatabaseCount('fun_game_podium', 0);
});

test('closing double awards both champions once and permits missing second and third places', function (UserRole $role) {
    $this->seed(ProductionSeeder::class);
    ['actor' => $actor, 'event' => $event, 'team1' => $team, 'players' => $players] = doubleFunGameFixture($role);

    $this->actingAs($actor)->post(route('fun-games.close', $event), ['champion_team_id' => $team->id])->assertRedirect(route('fun-games.show', $event))->assertSessionHasNoErrors();

    $this->assertDatabaseHas('fun_games', ['id' => $event->id, 'status' => 'completed', 'champion_id' => null, 'runner_up_id' => null, 'third_place_id' => null]);
    $this->assertDatabaseCount('fun_game_podium', 2);
    foreach ([$players[0], $players[1]] as $player) {
        $this->assertDatabaseHas('fun_game_podium', ['fun_game_id' => $event->id, 'user_id' => $player->id, 'position' => 1]);
        expect($player->badges()->where('slug', 'champion')->exists())->toBeTrue();
    }
    $roster = $this->get(route('roster'))->assertOk();
    expect($roster->viewData('podium')['first']['champion_count'])->toBe(1);
    expect($roster->viewData('podium')['first']['user']->id)->toBe($players[0]->id);
    expect($roster->viewData('podium')['second']['champion_count'])->toBe(1);
    expect($roster->viewData('podium')['second']['user']->id)->toBe($players[1]->id);

    $this->postJson(route('fun-games.close', $event), ['champion_team_id' => $team->id])->assertUnprocessable()->assertJsonValidationErrors('fun_game');

    $this->assertDatabaseCount('fun_game_podium', 2);
})->with([UserRole::Admin, UserRole::Superadmin]);

test('double podium records both members of each selected pair in their correct positions', function () {
    ['actor' => $actor, 'event' => $event, 'team1' => $champion, 'team2' => $runner] = doubleFunGameFixture();
    $players = User::factory()->count(2)->create();
    $third = FunGameTeam::create(['fun_game_id' => $event->id, 'player1_id' => $players[0]->id, 'player2_id' => $players[1]->id]);

    $this->actingAs($actor)->post(route('fun-games.close', $event), ['champion_team_id' => $champion->id, 'runner_up_team_id' => $runner->id, 'third_place_team_id' => $third->id])->assertSessionHasNoErrors();

    $this->assertDatabaseCount('fun_game_podium', 6);
    foreach ([1 => $champion, 2 => $runner, 3 => $third] as $position => $team) {
        foreach ([$team->player1_id, $team->player2_id] as $userId) {
            $this->assertDatabaseHas('fun_game_podium', ['fun_game_id' => $event->id, 'user_id' => $userId, 'position' => $position]);
        }
    }
});

test('invalid or repeated podium pairs return 422 without partially closing an event', function (string $case, string $field) {
    ['actor' => $actor, 'event' => $event, 'team1' => $team, 'team2' => $runner] = doubleFunGameFixture();
    $payload = match ($case) {
        'missing' => [], 'unknown' => ['champion_team_id' => 999999],
        'duplicate-second' => ['champion_team_id' => $team->id, 'runner_up_team_id' => $team->id],
        'duplicate-third' => ['champion_team_id' => $team->id, 'runner_up_team_id' => $runner->id, 'third_place_team_id' => $runner->id],
    };

    $this->actingAs($actor)->postJson(route('fun-games.close', $event), $payload)->assertUnprocessable()->assertJsonValidationErrors($field);

    $this->assertDatabaseHas('fun_games', ['id' => $event->id, 'status' => 'open']);
    $this->assertDatabaseCount('fun_game_podium', 0);
})->with(['champion required' => ['missing', 'champion_team_id'], 'unknown champion' => ['unknown', 'champion_team_id'], 'duplicate second' => ['duplicate-second', 'runner_up_team_id'], 'duplicate third' => ['duplicate-third', 'third_place_team_id']]);

test('a podium pair from a different event is refused', function () {
    ['actor' => $actor, 'event' => $event] = doubleFunGameFixture();
    $other = FunGame::create(['title' => 'Other', 'event_date' => '2026-10-01', 'format' => 'double', 'status' => FunGameStatus::Completed]);
    $players = User::factory()->count(2)->create();
    $team = FunGameTeam::create(['fun_game_id' => $other->id, 'player1_id' => $players[0]->id, 'player2_id' => $players[1]->id]);

    $this->actingAs($actor)->postJson(route('fun-games.close', $event), ['champion_team_id' => $team->id])->assertUnprocessable()->assertJsonValidationErrors('champion_team_id');

    $this->assertDatabaseHas('fun_games', ['id' => $event->id, 'status' => 'open']);
    $this->assertDatabaseCount('fun_game_podium', 0);
});

test('one double match appears for all four players and only counts towards event participation', function () {
    $fixture = doubleFunGameFixture();
    ['players' => $players] = $fixture;
    $match = doubleFunGameMatch($fixture);

    foreach ($players as $player) {
        expect($player->allMatches()->pluck('id')->all())->toBe([$match->id]);
        expect($player->funGamesParticipatedCount())->toBe(1);
        expect($player->totalMatchesCount())->toBe(0);
        expect($player->totalWinsCount())->toBe(0);
        expect($player->winRatePercentage())->toBe(0.0);
    }
});

test('double opponents and partners are excluded from individual head to head results', function (int $opponentIndex) {
    $fixture = doubleFunGameFixture();
    ['players' => $players] = $fixture;
    doubleFunGameMatch($fixture);

    $this->actingAs($players[0])->get(route('head-to-head', ['player1_id' => $players[0]->id, 'opponent_id' => $players[$opponentIndex]->id]))
        ->assertOk()->assertViewHas('h2h', fn (array $stats): bool => $stats['total_matches'] === 0 && $stats['user_wins'] === 0 && $stats['opponent_wins'] === 0);
})->with(['partner' => 1, 'opponent' => 2]);

test('double match participation unlocks consecutive event badges and champion milestones for both partners', function () {
    $this->seed(ProductionSeeder::class);
    $fixture = doubleFunGameFixture();
    ['actor' => $actor, 'event' => $firstEvent, 'players' => $players] = $fixture;
    $firstEvent->update(['event_date' => '2026-10-01']);
    $this->actingAs($actor);

    for ($number = 1; $number <= 4; $number++) {
        if ($number > 1) {
            $event = FunGame::create(['title' => 'Double '.$number, 'event_date' => '2026-10-0'.$number, 'format' => 'double', 'status' => FunGameStatus::Open, 'created_by' => $actor->id]);
            $team1 = FunGameTeam::create(['fun_game_id' => $event->id, 'player1_id' => $players[0]->id, 'player2_id' => $players[1]->id]);
            $team2 = FunGameTeam::create(['fun_game_id' => $event->id, 'player1_id' => $players[2]->id, 'player2_id' => $players[3]->id]);
            $fixture = ['actor' => $actor, 'event' => $event, 'payload' => ['team1_id' => $team1->id, 'team2_id' => $team2->id, 'winner_team_id' => $team1->id, 'player1_score' => 3, 'player2_score' => 1]];
        }
        doubleFunGameMatch($fixture);
        $this->post(route('fun-games.close', $fixture['event']), ['champion_team_id' => $fixture['payload']['team1_id']])->assertSessionHasNoErrors();
    }

    foreach ($players as $player) {
        expect($player->funGamesParticipatedCount())->toBe(4);
        expect($player->badges()->where('slug', 'electric-form')->exists())->toBeTrue();
        expect($player->badges()->where('slug', 'giant-killer')->exists())->toBeFalse();
    }
    foreach ([$players[0], $players[1]] as $player) {
        expect($player->badges()->whereIn('slug', ['champion', 'champion-2', 'champion-3'])->count())->toBe(3);
        expect($player->badges()->where('slug', 'champion-5')->exists())->toBeFalse();
    }
    expect($players[2]->badges()->where('slug', 'champion')->exists())->toBeFalse();
});

test('deleting a completed double event removes its pairs matches podiums and unsupported champion badges', function () {
    $this->seed(ProductionSeeder::class);
    $fixture = doubleFunGameFixture(UserRole::Superadmin);
    ['actor' => $actor, 'event' => $event, 'players' => $players, 'team1' => $team1, 'team2' => $team2] = $fixture;
    $match = doubleFunGameMatch($fixture);
    $this->actingAs($actor)->post(route('fun-games.close', $event), ['champion_team_id' => $team1->id, 'runner_up_team_id' => $team2->id])->assertSessionHasNoErrors();

    $this->delete(route('fun-games.destroy', $event), ['delete_confirmation' => 'HAPUS'])->assertRedirect(route('fun-games.index'))->assertSessionHasNoErrors();

    $this->assertModelMissing($event);
    $this->assertModelMissing($team1);
    $this->assertModelMissing($team2);
    $this->assertModelMissing($match);
    $this->assertDatabaseCount('fun_game_podium', 0);
    foreach ($players as $player) {
        $this->assertModelExists($player);
        expect($player->funGamesParticipatedCount())->toBe(0);
        expect($player->badges()->where('slug', 'champion')->exists())->toBeFalse();
    }
});

test('recent single performance and handicap evaluation ignore completed double events', function () {
    $fixture = doubleFunGameFixture();
    ['actor' => $actor, 'event' => $event, 'players' => $players, 'team1' => $team] = $fixture;
    doubleFunGameMatch($fixture);
    $this->actingAs($actor)->post(route('fun-games.close', $event), ['champion_team_id' => $team->id])->assertSessionHasNoErrors();

    foreach ($players as $player) {
        expect($player->recentFunGamesSummary())->toBe([]);
        expect($player->evaluateHandicap()['total_matches'])->toBe(0);
        expect($player->evaluateHandicap()['win_rate'])->toBe(0.0);
    }
});

test('home and roster history show double wins and scores from each partners perspective', function (int $playerIndex, bool $winner, string $score) {
    $fixture = doubleFunGameFixture();
    ['players' => $players] = $fixture;
    $match = doubleFunGameMatch($fixture);
    $player = $players[$playerIndex];

    $home = $this->actingAs($player)->get(route('home'))->assertOk()->assertSee('Double')->assertSee('Andi')->assertSee('Budi')->assertSee('Candra')->assertSee('Dedi');
    $profile = $this->get(route('roster.show', $player))->assertOk()->assertSee('Double')->assertSee($winner ? 'Menang' : 'Kalah');

    expect($home->viewData('recentMatches')->pluck('id')->all())->toBe([$match->id]);
    expect($profile->viewData('recentMatches')->pluck('id')->all())->toBe([$match->id]);
    expect($match->wonBy($player->id))->toBe($winner);
    expect($match->scoreFor($player->id))->toBe($score);
})->with([
    'winning first partner' => [0, true, '3 - 1'], 'winning second partner' => [1, true, '3 - 1'],
    'losing first partner' => [2, false, '1 - 3'], 'losing second partner' => [3, false, '1 - 3'],
]);
