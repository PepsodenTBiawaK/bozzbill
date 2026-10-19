<?php

use App\Enums\EvaluationRecommendation;
use App\Enums\FunGameStatus;
use App\Enums\Handicap;
use App\Enums\UserRole;
use App\Models\FunGame;
use App\Models\HcHistory;
use App\Models\MatchLog;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;

uses(RefreshDatabase::class);

/** @param list<int> $winsByEvent
 * @return array{player: User, opponents: Illuminate\Database\Eloquent\Collection, events: Collection}
 */
function evidenceFixture(array $winsByEvent = [4, 3, 3, 3], int $opponentCount = 5, Handicap $handicap = Handicap::ThreeN): array
{
    $player = User::factory()->create(['handicap' => $handicap]);
    $opponents = User::factory()->count($opponentCount)->create(['handicap' => Handicap::ThreeN]);
    $events = collect();
    foreach ($winsByEvent as $index => $wins) {
        $event = FunGame::create(['title' => 'FG Bukti '.$index, 'event_date' => CarbonImmutable::parse('2026-09-01')->addDays($index)->toDateString(), 'status' => FunGameStatus::Completed]);
        $events->push($event);
        for ($n = 0; $n < 5; $n++) {
            $opponent = $opponents[$n % $opponentCount];
            MatchLog::create(['fun_game_id' => $event->id, 'player1_id' => $player->id, 'player2_id' => $opponent->id,
                'winner_id' => $n < $wins ? $player->id : $opponent->id, 'player1_score' => $n < $wins ? 3 : 1,
                'player2_score' => $n < $wins ? 1 : 3, 'player1_hc' => $handicap, 'player2_hc' => Handicap::ThreeN]);
        }
    }

    return compact('player', 'opponents', 'events');
}

test('HC recommendations require sufficient evidence and consistent results without changing the account', function (array $wins, EvaluationRecommendation $expected) {
    ['player' => $player] = evidenceFixture($wins);
    $original = $player->fresh()->getAttributes();

    $evaluation = $player->evaluateHandicap();

    expect($evaluation['recommendation'])->toBe($expected);
    expect($evaluation['events_count'])->toBe(count($wins));
    expect($evaluation['opponents_count'])->toBe(5);
    expect($player->fresh()->getAttributes())->toBe($original);
    $this->assertDatabaseEmpty('hc_histories');
})->with([
    'promotion exactly 65 percent' => [[4, 3, 3, 3], EvaluationRecommendation::Promote],
    'demotion exactly 35 percent' => [[2, 2, 2, 1], EvaluationRecommendation::Demote],
    'middle performance' => [[3, 3, 2, 2], EvaluationRecommendation::Stay],
    'too few matches and events' => [[5, 5, 5], EvaluationRecommendation::Watch],
    'recent deterioration' => [[5, 5, 5, 5, 2, 2, 2, 2], EvaluationRecommendation::Watch],
    'recent recovery' => [[0, 0, 0, 0, 3, 3, 3, 3], EvaluationRecommendation::Watch],
]);

test('frequent wins against the same opponent do not meet the evidence requirement', function () {
    ['player' => $player] = evidenceFixture([5, 5, 5, 5], 1);
    $result = $player->evaluateHandicap();
    expect($result['recommendation'])->toBe(EvaluationRecommendation::Watch);
    expect($result['missing'])->toContain('Perlu 4 lawan berbeda lagi');
});

test('the evaluation uses participated events instead of the communitys latest events', function () {
    ['player' => $player] = evidenceFixture();
    for ($n = 1; $n <= 12; $n++) {
        FunGame::create(['title' => 'Tidak Diikuti '.$n, 'status' => FunGameStatus::Completed, 'event_date' => '2026-10-01']);
    }
    expect($player->evaluateHandicap()['total_matches'])->toBe(20);
    expect($player->evaluateHandicap()['recommendation'])->toBe(EvaluationRecommendation::Promote);
});

test('only the latest eight participated events are evaluated', function () {
    ['player' => $player] = evidenceFixture([5, 5, 3, 3, 3, 3, 3, 3, 3, 3]);
    $result = $player->evaluateHandicap();
    expect($result['total_matches'])->toBe(40);
    expect($result['wins'])->toBe(24);
    expect(array_column($result['event_results'], 'title'))->not->toContain('FG Bukti 0', 'FG Bukti 1');
});

test('HC changes start a new evidence period and opponent groups use historical HC', function () {
    ['player' => $player, 'opponents' => $opponents] = evidenceFixture();
    HcHistory::create(['user_id' => $player->id, 'old_hc' => Handicap::ThreeB, 'new_hc' => Handicap::ThreeN, 'reason' => 'Kenaikan resmi', 'approved_at' => '2026-09-03 20:00:00']);
    $opponents->each->update(['handicap' => Handicap::ThreePlus]);

    $result = $player->fresh()->evaluateHandicap();

    expect($result['total_matches'])->toBe(10);
    expect($result['other_hc_matches'])->toBe(10);
    expect($result['recommendation'])->toBe(EvaluationRecommendation::Watch);
    expect(collect($result['opponent_results'])->firstWhere('handicap', '3N')['matches'])->toBe(10);
    expect(collect($result['opponent_results'])->firstWhere('handicap', '3+')['matches'])->toBe(0);
});

test('incomplete histories are explained and excluded while unknown scores preserve valid win loss evidence', function () {
    ['player' => $player, 'events' => $events] = evidenceFixture();
    $events->first()->forceFill(['match_records_complete' => false])->save();
    MatchLog::query()->where('fun_game_id', $events->last()->id)->update(['player1_score' => null, 'player2_score' => null]);

    $result = $player->evaluateHandicap();

    expect($result['total_matches'])->toBe(15);
    expect($result['excluded_matches'])->toBe(5);
    expect($result['unknown_race_matches'])->toBe(5);
    expect($result['recommendation'])->toBe(EvaluationRecommendation::Watch);
});

test('old handicap snapshots and open cancelled or double events cannot strengthen an HC recommendation', function () {
    ['player' => $player, 'events' => $events] = evidenceFixture();
    MatchLog::where('fun_game_id', $events[0]->id)->update(['player1_hc' => '3B']);
    $events[1]->update(['status' => FunGameStatus::Open]);
    $events[2]->update(['status' => FunGameStatus::Cancelled]);
    $events[3]->update(['format' => 'double']);

    $result = $player->evaluateHandicap();
    expect($result['total_matches'])->toBe(0);
    expect($result['other_hc_matches'])->toBe(5);
    expect($result['recommendation'])->toBe(EvaluationRecommendation::Watch);
});

test('one unusually successful event cannot alone trigger promotion', function () {
    ['player' => $player, 'opponents' => $opponents, 'events' => $events] = evidenceFixture([5, 0, 0, 0]);
    for ($n = 0; $n < 60; $n++) {
        MatchLog::create(['fun_game_id' => $events[0]->id, 'player1_id' => $player->id, 'player2_id' => $opponents[$n % 5]->id,
            'winner_id' => $player->id, 'player1_score' => 3, 'player2_score' => 1, 'player1_hc' => '3N', 'player2_hc' => '3N']);
    }
    $result = $player->evaluateHandicap();
    expect($result['win_rate'])->toBeGreaterThan(65);
    expect($result['positive_events'])->toBe(1);
    expect($result['recommendation'])->toBe(EvaluationRecommendation::Watch);
});

test('HC recommendations respect the highest and lowest available levels', function (Handicap $handicap, array $wins) {
    ['player' => $player] = evidenceFixture($wins, 5, $handicap);
    $result = $player->evaluateHandicap();
    expect($result['recommendation'])->toBe(EvaluationRecommendation::Stay);
    expect($result['suggested_hc'])->toBe($handicap);
})->with([[Handicap::ThreePlus, [5, 5, 5, 5]], [Handicap::ThreeSb, [0, 0, 0, 0]]]);

test('the simulation command is read only and administrator views agree with the per player evaluation', function () {
    ['player' => $player] = evidenceFixture();
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $before = User::orderBy('id')->get()->map->getAttributes()->all();
    $this->artisan('bozzbill:evaluate-hc')->expectsOutputToContain('Simulasi selesai.')->assertSuccessful();
    $this->artisan('bozzbill:evaluate-hc', ['--json' => true])->assertSuccessful();
    expect(User::orderBy('id')->get()->map->getAttributes()->all())->toBe($before);
    $this->assertDatabaseEmpty('hc_histories');

    $this->actingAs($admin)->get(route('evaluating-hc.index'))->assertOk()->assertSee('Lihat Dasar Rekomendasi')->assertSee('65%');
    $response = $this->get(route('dashboard'))->assertOk();
    expect($response->viewData('recommendationCounts')['promote'])->toBe(1);
    expect($player->fresh()->evaluateHandicap()['recommendation'])->toBe(EvaluationRecommendation::Promote);
});
