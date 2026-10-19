<?php

use App\Enums\FunGameStatus;
use App\Enums\Handicap;
use App\Enums\UserRole;
use App\Models\Badge;
use App\Models\FunGame;
use App\Models\FunGameTeam;
use App\Models\MatchLog;
use App\Models\MatchLogCorrection;
use App\Models\User;
use Database\Seeders\ProductionSeeder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

/** @return array{actor: User, players: Collection, event: FunGame, match: MatchLog, payload: array<string, mixed>} */
function completedCorrectionFixture(UserRole $role = UserRole::Superadmin, bool $double = false): array
{
    $actor = User::factory()->create(['role' => $role, 'is_active' => true]);
    $players = User::factory()->count($double ? 4 : 2)->sequence(['handicap' => Handicap::ThreeB], ['handicap' => Handicap::ThreeA])->create();
    $event = FunGame::create([
        'title' => 'Histori koreksi', 'notes' => 'Catatan asli.', 'event_date' => '2026-10-01',
        'format' => $double ? 'double' : 'single', 'status' => FunGameStatus::Completed, 'created_by' => $actor->id,
        'champion_id' => $double ? null : $players[0]->id, 'runner_up_id' => $double ? null : $players[1]->id,
    ]);

    if ($double) {
        $team1 = FunGameTeam::create(['fun_game_id' => $event->id, 'player1_id' => $players[0]->id, 'player2_id' => $players[1]->id]);
        $team2 = FunGameTeam::create(['fun_game_id' => $event->id, 'player1_id' => $players[2]->id, 'player2_id' => $players[3]->id]);
        $event->podiumPlayers()->sync([$players[0]->id => ['position' => 1], $players[1]->id => ['position' => 1], $players[2]->id => ['position' => 2], $players[3]->id => ['position' => 2]]);
        $identities = ['team1_id' => $team1->id, 'team2_id' => $team2->id, 'winner_team_id' => $team1->id];
        $snapshots = [];
    } else {
        $identities = ['player1_id' => $players[0]->id, 'player2_id' => $players[1]->id, 'winner_id' => $players[0]->id];
        $snapshots = ['player1_hc' => Handicap::ThreeB, 'player2_hc' => Handicap::ThreeA];
    }

    $attributes = [...$identities, 'player1_score' => 3, 'player2_score' => 1, 'stage' => 'Penyisihan'];
    $match = MatchLog::create([...$attributes, ...$snapshots, 'fun_game_id' => $event->id, 'recorded_by' => $actor->id]);
    $match->refresh();
    $event->refresh();
    $payload = [
        ...$attributes, 'player1_score' => 1, 'player2_score' => 3,
        $double ? 'winner_team_id' : 'winner_id' => $double ? $identities['team2_id'] : $players[1]->id,
        'current_password' => 'password', 'correction_reason' => 'Skor pada catatan awal tertukar.',
        'correction_confirmed' => '1', 'match_version' => $match->correctionVersion(),
    ];

    return compact('actor', 'players', 'event', 'match', 'payload');
}

test('superadmins can open a completed match correction form with per-save safeguards', function () {
    ['actor' => $actor, 'event' => $event, 'match' => $match] = completedCorrectionFixture();

    $this->actingAs($actor)->get(route('fun-games.matches.edit', [$event, $match]))->assertOk()
        ->assertSee('name="current_password"', false)->assertSee('name="correction_reason"', false)
        ->assertSee('name="correction_confirmed"', false)->assertSee($match->correctionVersion(), false);
    $this->get(route('fun-games.show', $event))->assertOk()
        ->assertSee(route('fun-games.matches.edit', [$event, $match]), false)->assertDontSee('Ya, Hapus Match');
});

test('admins and members cannot open or submit completed match corrections', function (UserRole $role) {
    ['actor' => $actor, 'event' => $event, 'match' => $match, 'payload' => $payload] = completedCorrectionFixture($role);
    $original = $match->getAttributes();

    $this->actingAs($actor)->get(route('fun-games.matches.edit', [$event, $match]))->assertForbidden();
    $this->put(route('fun-games.matches.update', [$event, $match]), $payload)->assertForbidden();
    $this->get(route('fun-games.show', $event))->assertOk()->assertDontSee(route('fun-games.matches.edit', [$event, $match]), false);

    expect($match->fresh()->getAttributes())->toBe($original);
    $this->assertDatabaseCount('match_log_corrections', 0);
})->with([UserRole::Admin, UserRole::Member]);

test('guests must sign in before viewing or submitting completed match corrections', function () {
    ['event' => $event, 'match' => $match, 'payload' => $payload] = completedCorrectionFixture();

    $this->get(route('fun-games.matches.edit', [$event, $match]))->assertRedirect(route('login'));
    $this->put(route('fun-games.matches.update', [$event, $match]), $payload)->assertRedirect(route('login'));

    $this->assertDatabaseHas('match_logs', ['id' => $match->id, 'player1_score' => 3, 'player2_score' => 1]);
    $this->assertDatabaseCount('match_log_corrections', 0);
});

test('a correction preserves historical snapshots and podium while auditing changes and synchronizing winner badges', function () {
    $this->seed(ProductionSeeder::class);
    ['actor' => $actor, 'players' => $players, 'event' => $event, 'match' => $match, 'payload' => $payload] = completedCorrectionFixture();
    $giant = Badge::where('slug', 'giant-killer')->firstOrFail();
    $champion = Badge::where('slug', 'champion')->firstOrFail();
    $players[0]->badges()->attach([$giant->id, $champion->id]);
    $players[0]->update(['handicap' => Handicap::ThreePlus]);
    $players[1]->update(['handicap' => Handicap::ThreeB]);
    $originalEvent = $event->getAttributes();
    $createdAt = $match->created_at;

    $this->actingAs($actor)->put(route('fun-games.matches.update', [$event, $match]), [
        ...$payload, 'stage' => 'Final', 'correction_reason' => '  Skor terbalik pada catatan awal.  ',
        'recorded_by' => $players[0]->id, 'fun_game_id' => 999999, 'player1_hc' => '3B',
        'status' => 'open', 'champion_id' => $players[1]->id,
        'before_values' => ['winner_id' => $players[1]->id], 'changed_by' => $players[1]->id,
    ])->assertRedirect(route('fun-games.show', $event))->assertSessionHasNoErrors();

    $corrected = $match->fresh();
    expect($corrected->winner_id)->toBe($players[1]->id);
    expect($corrected->stage)->toBe('Final');
    expect($corrected->player1_hc)->toBe(Handicap::ThreeB);
    expect($corrected->player2_hc)->toBe(Handicap::ThreeA);
    expect($corrected->recorded_by)->toBe($actor->id);
    expect($corrected->created_at->equalTo($createdAt))->toBeTrue();
    expect($event->fresh()->getAttributes())->toBe($originalEvent);
    expect($players[0]->badges()->whereKey($giant->id)->exists())->toBeFalse();
    expect($players[0]->badges()->whereKey($champion->id)->exists())->toBeTrue();
    expect($players[1]->badges()->whereKey($giant->id)->exists())->toBeFalse();
    $this->assertDatabaseCount('match_log_corrections', 1);
    $audit = MatchLogCorrection::query()->firstOrFail();
    expect($audit->changed_by)->toBe($actor->id);
    expect($audit->reason)->toBe('Skor terbalik pada catatan awal.');
    expect($audit->before_values)->toMatchArray(['player1_score' => 3, 'player2_score' => 1, 'winner_id' => $players[0]->id, 'stage' => 'Penyisihan']);
    expect($audit->after_values)->toMatchArray(['player1_score' => 1, 'player2_score' => 3, 'winner_id' => $players[1]->id, 'stage' => 'Final']);
    expect($audit->before_values)->not->toHaveKeys(['current_password', 'password', 'correction_confirmed', 'match_version', 'correction_reason', 'recorded_by', 'changed_by']);
    expect($audit->after_values)->not->toHaveKeys(['current_password', 'password', 'correction_confirmed', 'match_version', 'correction_reason', 'recorded_by', 'changed_by']);
    $this->assertDatabaseHas('match_log_corrections', ['fun_game_id' => $event->id, 'match_log_id' => $match->id]);
});

test('invalid completed correction safeguards return 422 without changing history', function (string $field, mixed $value) {
    ['actor' => $actor, 'event' => $event, 'match' => $match, 'payload' => $payload] = completedCorrectionFixture();
    $original = $match->getAttributes();

    $this->actingAs($actor)->putJson(route('fun-games.matches.update', [$event, $match]), [...$payload, $field => $value])
        ->assertUnprocessable()->assertJsonValidationErrors($field);

    expect($match->fresh()->getAttributes())->toBe($original);
    $this->assertDatabaseCount('match_log_corrections', 0);
})->with([
    'missing password' => ['current_password', null], 'wrong password' => ['current_password', 'wrong-password'],
    'blank reason' => ['correction_reason', '    '], 'missing reason' => ['correction_reason', null],
    'oversized reason' => ['correction_reason', str_repeat('a', 501)],
    'missing consent' => ['correction_confirmed', null], 'refused consent' => ['correction_confirmed', '0'],
    'missing version' => ['match_version', null], 'stale version' => ['match_version', str_repeat('0', 64)],
]);

test('completed single corrections reject tied scores or a winner with the lower score', function (string $field, mixed $value, string $errorField) {
    ['actor' => $actor, 'event' => $event, 'match' => $match, 'payload' => $payload] = completedCorrectionFixture();
    $original = $match->getAttributes();
    $payload[$field] = $value === 'original_winner' ? $match->winner_id : $value;

    $this->actingAs($actor)->putJson(route('fun-games.matches.update', [$event, $match]), $payload)
        ->assertUnprocessable()->assertJsonValidationErrors($errorField);

    expect($match->fresh()->getAttributes())->toBe($original);
    $this->assertDatabaseCount('match_log_corrections', 0);
})->with(['tied score' => ['player1_score', 3, 'player2_score'], 'incorrect winner' => ['winner_id', 'original_winner', 'winner_id']]);

test('the current database role is required even when the session model still has superadmin privileges', function (UserRole $newRole) {
    ['actor' => $actor, 'event' => $event, 'match' => $match, 'payload' => $payload] = completedCorrectionFixture();
    $this->actingAs($actor);
    User::query()->whereKey($actor->id)->update(['role' => $newRole]);

    $this->put(route('fun-games.matches.update', [$event, $match]), $payload)->assertForbidden();
    $this->get(route('fun-games.matches.edit', [$event, $match]))->assertForbidden();

    $this->assertDatabaseHas('match_logs', ['id' => $match->id, 'player1_score' => 3]);
    $this->assertDatabaseCount('match_log_corrections', 0);
})->with([UserRole::Admin, UserRole::Member]);

test('password verification uses the current database hash rather than the stale session model', function () {
    ['actor' => $actor, 'event' => $event, 'match' => $match, 'payload' => $payload] = completedCorrectionFixture();
    $this->actingAs($actor);
    User::query()->whereKey($actor->id)->update(['password' => Hash::make('new-current-password')]);

    $this->putJson(route('fun-games.matches.update', [$event, $match]), $payload)->assertUnprocessable()->assertJsonValidationErrors('current_password');
    $this->assertDatabaseHas('match_logs', ['id' => $match->id, 'player1_score' => 3]);
    $this->assertDatabaseCount('match_log_corrections', 0);

    $this->put(route('fun-games.matches.update', [$event, $match]), [...$payload, 'current_password' => 'new-current-password'])
        ->assertRedirect(route('fun-games.show', $event))->assertSessionHasNoErrors();
    $this->assertDatabaseCount('match_log_corrections', 1);
});

test('five incorrect passwords lock further corrections until the one minute window expires', function (string $cacheStore) {
    config(['cache.default' => $cacheStore]);
    $this->freezeTime();
    ['actor' => $actor, 'event' => $event, 'match' => $match, 'payload' => $payload] = completedCorrectionFixture();
    $this->actingAs($actor);

    for ($attempt = 0; $attempt < 5; $attempt++) {
        $this->putJson(route('fun-games.matches.update', [$event, $match]), [...$payload, 'current_password' => 'incorrect-password'])
            ->assertUnprocessable()->assertJsonValidationErrors('current_password');
    }
    $this->putJson(route('fun-games.matches.update', [$event, $match]), $payload)->assertTooManyRequests();
    $this->assertDatabaseHas('match_logs', ['id' => $match->id, 'player1_score' => 3]);
    $this->assertDatabaseCount('match_log_corrections', 0);

    $this->travel(61)->seconds();
    $this->put(route('fun-games.matches.update', [$event, $match]), $payload)
        ->assertRedirect(route('fun-games.show', $event))->assertSessionHasNoErrors();
    $this->assertDatabaseCount('match_log_corrections', 1);
})->with(['array', 'database']);

test('password verification must be supplied again for each completed correction', function () {
    ['actor' => $actor, 'event' => $event, 'match' => $match, 'payload' => $payload] = completedCorrectionFixture();
    $this->actingAs($actor)->put(route('fun-games.matches.update', [$event, $match]), $payload)->assertSessionHasNoErrors();

    $this->putJson(route('fun-games.matches.update', [$event, $match]), [
        ...$payload, 'stage' => 'Final', 'match_version' => $match->fresh()->correctionVersion(), 'current_password' => null,
    ])->assertUnprocessable()->assertJsonValidationErrors('current_password');

    expect($match->fresh()->stage)->toBe('Penyisihan');
    $this->assertDatabaseCount('match_log_corrections', 1);
});

test('stale forms cannot overwrite a correction saved during the same second', function () {
    $this->freezeTime();
    ['actor' => $actor, 'event' => $event, 'match' => $match, 'payload' => $payload] = completedCorrectionFixture();
    $this->actingAs($actor)->put(route('fun-games.matches.update', [$event, $match]), $payload)->assertSessionHasNoErrors();

    $this->putJson(route('fun-games.matches.update', [$event, $match]), [...$payload, 'stage' => 'Final'])
        ->assertUnprocessable()->assertJsonValidationErrors('match_version');

    $this->assertDatabaseHas('match_logs', ['id' => $match->id, 'stage' => 'Penyisihan', 'player1_score' => 1, 'player2_score' => 3]);
    $this->assertDatabaseCount('match_log_corrections', 1);
});

test('an unchanged submission does not create a misleading correction audit', function () {
    ['actor' => $actor, 'event' => $event, 'match' => $match, 'payload' => $payload] = completedCorrectionFixture();

    $this->actingAs($actor)->putJson(route('fun-games.matches.update', [$event, $match]), [
        ...$payload, 'player1_score' => 3, 'player2_score' => 1, 'winner_id' => $match->winner_id,
    ])->assertUnprocessable()->assertJsonValidationErrors('match');

    $this->assertDatabaseCount('match_log_corrections', 0);
});

test('double corrections update one match and audit while keeping all podium members unchanged', function () {
    ['actor' => $actor, 'players' => $players, 'event' => $event, 'match' => $match, 'payload' => $payload] = completedCorrectionFixture(double: true);
    $originalPodium = DB::table('fun_game_podium')->where('fun_game_id', $event->id)->orderBy('id')->get()->toJson();

    $this->actingAs($actor)->put(route('fun-games.matches.update', [$event, $match]), $payload)
        ->assertRedirect(route('fun-games.show', $event))->assertSessionHasNoErrors();

    $this->assertDatabaseHas('match_logs', ['id' => $match->id, 'winner_team_id' => $match->team2_id, 'player1_score' => 1, 'player2_score' => 3, 'winner_id' => null]);
    expect(DB::table('fun_game_podium')->where('fun_game_id', $event->id)->orderBy('id')->get()->toJson())->toBe($originalPodium);
    $this->assertDatabaseHas('fun_games', ['id' => $event->id, 'status' => 'completed', 'format' => 'double']);
    $this->assertDatabaseCount('match_logs', 1);
    $this->assertDatabaseCount('match_log_corrections', 1);
    foreach ($players as $player) {
        expect($player->allMatches()->pluck('id')->all())->toBe([$match->id]);
        expect($player->totalMatchesCount())->toBe(0);
    }
});

test('completed corrections cannot target a match through a different event address', function () {
    ['actor' => $actor, 'event' => $event, 'match' => $match, 'payload' => $payload] = completedCorrectionFixture();
    $other = $event->replicate();
    $other->save();

    $this->actingAs($actor)->get(route('fun-games.matches.edit', [$other, $match]))->assertNotFound();
    $this->put(route('fun-games.matches.update', [$other, $match]), $payload)->assertNotFound();

    $this->assertDatabaseHas('match_logs', ['id' => $match->id, 'fun_game_id' => $event->id, 'player1_score' => 3]);
    $this->assertDatabaseCount('match_log_corrections', 0);
});

test('superadmins cannot correct cancelled events or add or delete matches in a completed event', function () {
    ['actor' => $actor, 'event' => $event, 'match' => $match, 'payload' => $payload] = completedCorrectionFixture();
    $this->actingAs($actor)->postJson(route('fun-games.matches.store', $event), $payload)->assertUnprocessable()->assertJsonValidationErrors('fun_game');
    $this->deleteJson(route('fun-games.matches.destroy', [$event, $match]))->assertUnprocessable()->assertJsonValidationErrors('fun_game');
    $event->update(['status' => FunGameStatus::Cancelled]);

    $this->get(route('fun-games.matches.edit', [$event, $match]))->assertForbidden();
    $this->putJson(route('fun-games.matches.update', [$event, $match]), $payload)->assertUnprocessable()->assertJsonValidationErrors('fun_game');

    $this->assertDatabaseCount('match_logs', 1);
    $this->assertDatabaseCount('match_log_corrections', 0);
});

test('correction passwords are never flashed when a form returns with validation errors', function () {
    ['actor' => $actor, 'event' => $event, 'match' => $match, 'payload' => $payload] = completedCorrectionFixture();

    $this->actingAs($actor)->from(route('fun-games.matches.edit', [$event, $match]))
        ->put(route('fun-games.matches.update', [$event, $match]), [...$payload, 'correction_reason' => ''])
        ->assertRedirect(route('fun-games.matches.edit', [$event, $match]))->assertSessionHasErrors('correction_reason');

    expect(session()->getOldInput())->not->toHaveKey('current_password');
    $this->assertDatabaseCount('match_log_corrections', 0);
});

test('failure to save the audit rolls back the match and badges together', function () {
    $this->seed(ProductionSeeder::class);
    ['actor' => $actor, 'players' => $players, 'event' => $event, 'match' => $match, 'payload' => $payload] = completedCorrectionFixture();
    $badge = Badge::where('slug', 'giant-killer')->firstOrFail();
    $players[0]->badges()->attach($badge);
    $original = $match->getAttributes();
    Event::listen('eloquent.creating: '.MatchLogCorrection::class, function (): void {
        throw new RuntimeException('Audit storage is unavailable.');
    });

    $this->actingAs($actor)->put(route('fun-games.matches.update', [$event, $match]), $payload)->assertInternalServerError();

    expect($match->fresh()->getAttributes())->toBe($original);
    expect($players[0]->badges()->whereKey($badge->id)->exists())->toBeTrue();
    $this->assertDatabaseCount('match_log_corrections', 0);
});

test('replacing a participant synchronizes consecutive event badges for both the old and new player', function () {
    $this->seed(ProductionSeeder::class);
    ['actor' => $actor, 'players' => $players, 'event' => $event, 'match' => $match, 'payload' => $payload] = completedCorrectionFixture();
    $replacement = User::factory()->create(['handicap' => Handicap::ThreeN]);
    $event->update(['event_date' => '2026-10-04', 'runner_up_id' => null]);
    for ($day = 1; $day <= 3; $day++) {
        $previous = FunGame::create(['title' => 'Histori '.$day, 'event_date' => '2026-10-0'.$day, 'status' => FunGameStatus::Completed]);
        MatchLog::create([
            'fun_game_id' => $previous->id, 'player1_id' => $players[1]->id, 'player2_id' => $replacement->id,
            'winner_id' => $players[1]->id, 'player1_score' => 3, 'player2_score' => 1, 'recorded_by' => $actor->id,
        ]);
    }
    $players[1]->syncAchievementBadges(['streak']);
    expect($players[1]->badges()->where('slug', 'electric-form')->exists())->toBeTrue();
    expect($replacement->badges()->where('slug', 'electric-form')->exists())->toBeFalse();

    $this->actingAs($actor)->put(route('fun-games.matches.update', [$event, $match]), [
        ...$payload, 'player2_id' => $replacement->id, 'winner_id' => $replacement->id,
    ])->assertSessionHasNoErrors()->assertRedirect(route('fun-games.show', $event));

    expect($players[1]->badges()->where('slug', 'electric-form')->exists())->toBeFalse();
    expect($replacement->badges()->where('slug', 'electric-form')->exists())->toBeTrue();
    expect($match->fresh()->player1_hc)->toBe(Handicap::ThreeB);
    expect($match->fresh()->player2_hc)->toBe(Handicap::ThreeN);
});

test('each successful correction appends an audit without altering an earlier correction', function () {
    ['actor' => $actor, 'players' => $players, 'event' => $event, 'match' => $match, 'payload' => $payload] = completedCorrectionFixture();
    $this->actingAs($actor)->put(route('fun-games.matches.update', [$event, $match]), $payload)->assertSessionHasNoErrors();
    $firstAudit = MatchLogCorrection::query()->firstOrFail();
    $originalAudit = $firstAudit->getAttributes();

    $this->put(route('fun-games.matches.update', [$event, $match]), [
        ...$payload, 'player1_score' => 3, 'player2_score' => 1, 'winner_id' => $players[0]->id,
        'match_version' => $match->fresh()->correctionVersion(), 'correction_reason' => 'Koreksi hasil yang telah diperiksa ulang.',
    ])->assertSessionHasNoErrors();

    expect($firstAudit->fresh()->getAttributes())->toBe($originalAudit);
    $this->assertDatabaseCount('match_log_corrections', 2);
    $this->putJson(route('fun-games.matches.update', [$event, $match]), $payload)
        ->assertUnprocessable()->assertJsonValidationErrors('match_version');
    $this->assertDatabaseCount('match_log_corrections', 2);
});

test('correction reasons are escaped and visible only to superadmins in event history', function () {
    ['actor' => $actor, 'event' => $event, 'match' => $match, 'payload' => $payload] = completedCorrectionFixture();
    $reason = '<script>alert("private correction")</script>';
    $this->actingAs($actor)->put(route('fun-games.matches.update', [$event, $match]), [...$payload, 'correction_reason' => $reason])->assertSessionHasNoErrors();

    $this->get(route('fun-games.show', $event))->assertOk()->assertSee('Catatan Koreksi Histori')
        ->assertSee('Sebelum')->assertSee('Sesudah')->assertSee($match->player1->name)->assertSee($match->player2->name)
        ->assertSee($reason)->assertDontSee($reason, false);
    foreach ([UserRole::Admin, UserRole::Member] as $role) {
        $viewer = User::factory()->create(['role' => $role]);
        $this->actingAs($viewer)->get(route('fun-games.show', $event))->assertOk()
            ->assertDontSee('Catatan Koreksi Histori')->assertDontSee($reason);
    }
});

test('returning from a stale form error preserves its old token until the form is explicitly reloaded', function () {
    ['actor' => $actor, 'event' => $event, 'match' => $match, 'payload' => $payload] = completedCorrectionFixture();
    $oldVersion = $payload['match_version'];
    $match->update(['stage' => 'Final']);
    $newVersion = $match->fresh()->correctionVersion();
    $editUrl = route('fun-games.matches.edit', [$event, $match]);

    $this->actingAs($actor)->from($editUrl)->put(route('fun-games.matches.update', [$event, $match]), $payload)
        ->assertRedirect($editUrl)->assertSessionHasErrors('match_version');
    $this->withCookie(config('session.cookie'), session()->getId());
    $response = $this->get($editUrl);
    expect($response->viewData('errors')->has('match_version'))->toBeTrue();
    $response->assertOk()->assertSee('name="match_version" value="'.$oldVersion.'"', false)
        ->assertDontSee('name="match_version" value="'.$newVersion.'"', false)->assertSee('Muat ulang data match');

    $this->assertDatabaseCount('match_log_corrections', 0);
});

test('active match editing keeps its existing controls without completed correction safeguards', function () {
    ['actor' => $actor, 'event' => $event, 'match' => $match] = completedCorrectionFixture(UserRole::Admin);
    $event->update(['status' => FunGameStatus::Open]);

    $this->actingAs($actor)->get(route('fun-games.matches.edit', [$event, $match]))->assertOk()->assertSee('Simpan Perubahan')
        ->assertDontSee('name="current_password"', false)->assertDontSee('name="correction_reason"', false)
        ->assertDontSee('name="correction_confirmed"', false);
});

test('a match version remains stable after an unchanged newly created match is hydrated from the database', function () {
    ['actor' => $actor, 'players' => $players, 'event' => $event] = completedCorrectionFixture();
    $match = MatchLog::create([
        'stage' => 'Final', 'winner_id' => $players[0]->id,
        'player2_score' => 1, 'player1_score' => 3,
        'player2_id' => $players[1]->id, 'player1_id' => $players[0]->id,
        'recorded_by' => $actor->id, 'fun_game_id' => $event->id,
        'player1_hc' => Handicap::ThreeB, 'player2_hc' => Handicap::ThreeA,
    ]);

    expect($match->correctionVersion())->toBe($match->fresh()->correctionVersion());
});

test('migration rollback refuses to discard existing correction history', function () {
    ['actor' => $actor, 'event' => $event, 'match' => $match, 'payload' => $payload] = completedCorrectionFixture();
    $this->actingAs($actor)->put(route('fun-games.matches.update', [$event, $match]), $payload)->assertSessionHasNoErrors();
    $migration = require database_path('migrations/2026_10_19_041851_create_match_log_corrections_table.php');

    expect(fn () => $migration->down())->toThrow(RuntimeException::class, 'riwayat koreksi match harus tetap dipertahankan');

    $this->assertDatabaseCount('match_log_corrections', 1);
});
