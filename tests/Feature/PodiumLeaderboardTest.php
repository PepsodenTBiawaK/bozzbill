<?php

use App\Enums\FunGameStatus;
use App\Models\FunGame;
use App\Models\MatchLog;
use App\Models\PlayerDrill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('default leaderboard ranks all recorded podiums before titles and breaks ties by first then second places', function () {
    $frequent = User::factory()->create(['name' => 'Sering Podium']);
    $champion = User::factory()->create(['name' => 'Champion']);
    $runner = User::factory()->create(['name' => 'Runner']);
    $third = User::factory()->create(['name' => 'Ketiga']);
    $zeroLast = User::factory()->create(['name' => 'Zain']);
    $zeroFirst = User::factory()->create(['name' => 'Adi']);

    for ($number = 1; $number <= 4; $number++) {
        FunGame::create([
            'title' => 'FG '.$number, 'event_date' => '2026-10-01', 'status' => FunGameStatus::Completed,
            'champion_id' => $number <= 3 ? $champion->id : null,
            'runner_up_id' => $number <= 3 ? $runner->id : null,
            'third_place_id' => $frequent->id,
        ]);
    }
    for ($number = 1; $number <= 3; $number++) {
        FunGame::create(['title' => 'Arsip '.$number, 'event_date' => '2026-09-01', 'status' => FunGameStatus::Completed, 'third_place_id' => $third->id]);
    }

    $response = $this->actingAs($zeroLast)->get(route('roster'));

    $response->assertOk()->assertSee('Podium Terbanyak')->assertSee('4&times; Podium', false)
        ->assertViewHas('sort', 'most_podium');
    $podium = $response->viewData('podium');
    expect($podium['first']['user']->id)->toBe($frequent->id);
    expect($podium['first']['podium_count'])->toBe(4);
    expect($podium['first']['third_place_count'])->toBe(4);
    expect($podium['first']['total_matches'])->toBe(0);
    expect($podium['second']['user']->id)->toBe($champion->id);
    expect($podium['second']['champion_count'])->toBe(3);
    expect($podium['third']['user']->id)->toBe($runner->id);
    expect($podium['third']['runner_up_count'])->toBe(3);
    expect($response->viewData('restRankings')->pluck('user.id')->all())->toBe([$third->id, $zeroFirst->id, $zeroLast->id]);
    expect($response->viewData('restRankings')->last()['podium_count'])->toBe(0);
});

test('double partners each receive one podium and duplicated legacy columns do not count twice or include unfinished events', function () {
    $champion = User::factory()->create(['name' => 'Andi']);
    $partner = User::factory()->create(['name' => 'Budi']);
    $runner = User::factory()->create(['name' => 'Candra']);
    $third = User::factory()->create(['name' => 'Dedi']);
    $event = FunGame::create([
        'title' => 'Double', 'event_date' => '2026-09-01', 'status' => FunGameStatus::Completed,
        'champion_id' => $champion->id, 'runner_up_id' => $runner->id, 'third_place_id' => $third->id,
    ]);
    $event->forceFill(['format' => 'double'])->save();
    $event->podiumPlayers()->attach([
        $champion->id => ['position' => 1], $partner->id => ['position' => 1],
        $runner->id => ['position' => 2], $third->id => ['position' => 3],
    ]);
    foreach ([FunGameStatus::Open, FunGameStatus::Cancelled] as $status) {
        $excluded = FunGame::create(['title' => $status->value, 'event_date' => '2026-10-01', 'status' => $status, 'champion_id' => $third->id]);
        $excluded->podiumPlayers()->attach($third, ['position' => 1]);
    }

    $response = $this->actingAs($champion)->get(route('roster'))->assertOk();

    $podium = $response->viewData('podium');
    expect($podium['first']['user']->id)->toBe($champion->id);
    expect($podium['first']['champion_count'])->toBe(1);
    expect($podium['second']['user']->id)->toBe($partner->id);
    expect($podium['second']['champion_count'])->toBe(1);
    expect($podium['third']['user']->id)->toBe($runner->id);
    expect($podium['third']['runner_up_count'])->toBe(1);
    $last = $response->viewData('restRankings')->sole();
    expect($last['user']->id)->toBe($third->id);
    expect([$last['podium_count'], $last['champion_count'], $last['third_place_count']])->toBe([1, 0, 1]);
});

test('leaderboard filters keep their own ranking criteria and unknown filters fall back to podiums', function (string $sort, string $winnerName, string $selectedSort) {
    $champion = User::factory()->create(['name' => 'Champion']);
    $active = User::factory()->create(['name' => 'Aktif']);
    $winRate = User::factory()->create(['name' => 'Win Rate']);
    $driller = User::factory()->create(['name' => 'Drilling']);
    $event = FunGame::create(['title' => 'Selesai', 'event_date' => '2026-10-01', 'status' => FunGameStatus::Completed, 'champion_id' => $champion->id]);
    foreach ([[$active, $champion, $active], [$active, $champion, $champion], [$active, $driller, $active], [$winRate, $driller, $winRate]] as [$playerOne, $playerTwo, $winner]) {
        MatchLog::create([
            'fun_game_id' => $event->id, 'player1_id' => $playerOne->id, 'player2_id' => $playerTwo->id,
            'winner_id' => $winner->id, 'player1_score' => 3, 'player2_score' => 1,
            'player1_hc' => '3N', 'player2_hc' => '3N', 'stage' => 'Penyisihan', 'recorded_by' => $champion->id,
        ]);
    }
    PlayerDrill::create(['user_id' => $driller->id, 'drill_name' => 'Straight Shot', 'success_count' => 8, 'fail_count' => 2, 'practice_date' => '2026-10-01']);

    if ($sort === 'win_rate') {
        for ($number = 0; $number < 3; $number++) {
            $additional = FunGame::create(['title' => 'Kualifikasi '.$number, 'event_date' => '2026-10-02', 'status' => FunGameStatus::Completed]);
            for ($match = 0; $match < 3; $match++) {
                MatchLog::create(['fun_game_id' => $additional->id, 'player1_id' => $winRate->id, 'player2_id' => $driller->id,
                    'winner_id' => $winRate->id, 'player1_score' => 3, 'player2_score' => 1, 'player1_hc' => '3N', 'player2_hc' => '3N']);
            }
        }
    }

    $response = $this->actingAs($champion)->get(route('roster', ['sort' => $sort]));

    $response->assertOk()->assertViewHas('sort', $selectedSort);
    expect($response->viewData('podium')['first']['user']->name)->toBe($winnerName);
})->with([
    'podium' => ['most_podium', 'Champion', 'most_podium'],
    'win rate' => ['win_rate', 'Win Rate', 'win_rate'],
    'active' => ['most_active', 'Aktif', 'most_active'],
    'drilling' => ['most_drills', 'Drilling', 'most_drills'],
    'unknown' => ['unknown', 'Champion', 'most_podium'],
]);
