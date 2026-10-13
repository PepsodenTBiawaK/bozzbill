<?php

use App\Enums\FunGameStatus;
use App\Models\FunGame;
use App\Models\FunGameTeam;
use App\Models\MatchLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Arr;

beforeEach(function () {
    $this->artisan('migrate:fresh')->assertSuccessful();
});

afterEach(function () {
    RefreshDatabaseState::$migrated = false;
});

test('the doubles schema upgrade preserves an existing single match and its historical identifiers', function () {
    $players = User::factory()->count(2)->create();
    $event = FunGame::create(['title' => 'Histori Single', 'event_date' => '2026-09-06', 'status' => FunGameStatus::Completed]);
    $match = MatchLog::create([
        'fun_game_id' => $event->id,
        'player1_id' => $players[0]->id,
        'player2_id' => $players[1]->id,
        'winner_id' => $players[0]->id,
        'player1_score' => 3,
        'player2_score' => 1,
        'player1_hc' => '3B',
        'player2_hc' => '3N',
        'stage' => 'Final',
    ]);
    $match->forceFill(['source_key' => 'historical:single:match'])->save();
    $original = Arr::except($match->fresh()->getAttributes(), ['team1_id', 'team2_id', 'winner_team_id']);
    $migration = require database_path('migrations/2026_10_13_021119_support_doubles_fun_game_matches.php');
    $migration->down();

    $migration->up();

    expect(Arr::except($match->fresh()->getAttributes(), ['team1_id', 'team2_id', 'winner_team_id']))->toEqual($original);
    $this->assertDatabaseHas('fun_games', ['id' => $event->id, 'title' => 'Histori Single', 'status' => 'completed', 'format' => 'single']);
    $this->assertDatabaseCount('match_logs', 1);
    $event->delete();
    $players->each->delete();
});

test('a schema rollback refuses to remove a recorded event pair', function () {
    $players = User::factory()->count(2)->create();
    $event = FunGame::create(['title' => 'Double', 'event_date' => '2026-09-06', 'status' => FunGameStatus::Open, 'format' => 'double']);
    $team = FunGameTeam::create(['fun_game_id' => $event->id, 'player1_id' => $players[0]->id, 'player2_id' => $players[1]->id]);
    $migration = require database_path('migrations/2026_10_13_021119_support_doubles_fun_game_matches.php');

    expect(fn () => $migration->down())->toThrow(RuntimeException::class, 'Rollback ditolak: pasangan dan pertandingan double harus tetap dipertahankan.');

    $this->assertDatabaseHas('fun_game_teams', ['id' => $team->id, 'fun_game_id' => $event->id]);
    $event->delete();
    $players->each->delete();
});
