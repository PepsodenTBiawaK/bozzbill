<?php

use App\Enums\FunGameStatus;
use App\Models\FunGame;
use App\Models\MatchLog;
use App\Models\User;
use App\WinRateRanking;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function rankedResults(User $user, int $wins, int $total, int $events = 3, FunGameStatus $status = FunGameStatus::Completed, string $format = 'single'): void
{
    $opponent = User::factory()->create();
    $eventRows = collect(range(1, $events))->map(fn (int $n) => FunGame::create(['title' => 'Statistik '.$n, 'event_date' => '2026-10-01', 'status' => $status, 'format' => $format]));
    for ($n = 0; $n < $total; $n++) {
        MatchLog::create(['fun_game_id' => $eventRows[$n % $events]->id, 'player1_id' => $user->id, 'player2_id' => $opponent->id, 'winner_id' => $n < $wins ? $user->id : $opponent->id, 'player1_score' => $n < $wins ? 3 : 1, 'player2_score' => $n < $wins ? 1 : 3]);
    }
}

test('the win rate podium excludes newcomers and orders eligible members by evidence instead of raw percentage', function () {
    $long = User::factory()->create(['name' => 'Bukti Panjang']);
    $short = User::factory()->create(['name' => 'Bukti Pendek']);
    $new = User::factory()->create(['name' => 'Baru Sekali']);
    $oneEvent = User::factory()->create(['name' => 'Satu Event']);
    rankedResults($long, 20, 25);
    rankedResults($short, 8, 10);
    rankedResults($new, 1, 1, 1);
    rankedResults($oneEvent, 10, 10, 1);

    $response = $this->actingAs($new)->get(route('roster', ['sort' => 'win_rate']))->assertOk();
    expect($response->viewData('podium')['first']['user']->id)->toBe($long->id);
    expect($response->viewData('podium')['second']['user']->id)->toBe($short->id);
    expect($response->viewData('unrankedMembers')->pluck('user.id')->all())->toContain($new->id, $oneEvent->id);
    expect($response->viewData('podium')['first']['ranking_win_rate'])->toBe(80.0);
    $response->assertSee('Wilson')->assertSee('Belum Memenuhi Minimum Pertandingan')->assertSee('Baru Sekali');
});

test('the win rate qualification requires both ten matches and three completed singles events', function () {
    $user = User::factory()->create();
    rankedResults($user, 9, 9);
    rankedResults($user, 10, 10, 1, FunGameStatus::Open);
    rankedResults($user, 10, 10, 1, FunGameStatus::Cancelled);
    rankedResults($user, 10, 10, 1, FunGameStatus::Completed, 'double');
    $result = WinRateRanking::standings()->get($user->id);
    expect($result['total'])->toBe(9);
    expect($result['events'])->toBe(3);
    expect($result['eligible'])->toBeFalse();

    rankedResults($user, 1, 1, 1);
    expect(WinRateRanking::standings()->get($user->id)['eligible'])->toBeTrue();
});

test('wilson ranking scores match fixed reference examples and handle no matches', function () {
    expect(round(WinRateRanking::score(8, 10), 1))->toBe(49.0);
    expect(round(WinRateRanking::score(20, 25), 1))->toBe(60.9);
    expect(WinRateRanking::score(0, 0))->toBe(0.0);
});

test('an empty eligible ranking shows an explanation instead of assigning a provisional champion', function () {
    $user = User::factory()->create();
    $response = $this->actingAs($user)->get(route('roster', ['sort' => 'win_rate']))->assertOk();
    expect($response->viewData('podium')['first'])->toBeNull();
    $response->assertSee('Belum ada pemain yang memenuhi minimum peringkat.')->assertSee('Kurang 10 match');
});
