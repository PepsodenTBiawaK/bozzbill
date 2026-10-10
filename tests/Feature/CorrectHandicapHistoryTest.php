<?php

use App\Enums\FunGameStatus;
use App\Enums\Handicap;
use App\Enums\UserRole;
use App\Models\FunGame;
use App\Models\HcHistory;
use App\Models\MatchLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('historical correction previews without writes then aligns imported snapshots and records one effective transition', function () {
    $actor = User::factory()->create(['role' => UserRole::Superadmin]);
    $player = User::factory()->create(['handicap' => Handicap::ThreeA]);
    $opponent = User::factory()->create();
    $before = FunGame::create(['title' => 'FG 18', 'event_date' => '2026-09-13', 'status' => FunGameStatus::Completed]);
    $before->forceFill(['source_key' => 'archive:event:18'])->save();
    $after = FunGame::create(['title' => 'FG 19', 'event_date' => '2026-09-27', 'status' => FunGameStatus::Completed]);
    $matches = collect([$before, $after])->map(function ($event, $index) use ($player, $opponent): MatchLog {
        $match = MatchLog::create(['fun_game_id' => $event->id, 'player1_id' => $player->id, 'player2_id' => $opponent->id,
            'player1_score' => null, 'player2_score' => null, 'winner_id' => $player->id, 'player1_hc' => Handicap::ThreeA, 'player2_hc' => $opponent->handicap]);
        $match->forceFill(['source_key' => 'archive:match:'.$index])->save();

        return $match;
    });
    $manual = MatchLog::create(['fun_game_id' => $before->id, 'player1_id' => $player->id, 'player2_id' => $opponent->id,
        'player1_score' => 3, 'player2_score' => 1, 'winner_id' => $player->id, 'player1_hc' => Handicap::ThreeA, 'player2_hc' => $opponent->handicap]);
    $arguments = ['user' => $player->id, 'event' => $before->id, 'old' => '3N', 'new' => '3A', '--by' => $actor->id];

    $this->artisan('bozzbill:correct-hc-history', $arguments)->expectsOutputToContain('Pratinjau')->assertExitCode(0);
    $this->assertDatabaseEmpty('hc_histories');
    expect($matches[0]->fresh()->player1_hc)->toBe(Handicap::ThreeA);
    $this->artisan('bozzbill:correct-hc-history', [...$arguments, '--apply' => true])->assertExitCode(0);
    $this->artisan('bozzbill:correct-hc-history', [...$arguments, '--apply' => true])->assertExitCode(0);

    $this->assertDatabaseCount('hc_histories', 1);
    $this->assertDatabaseHas('hc_histories', ['user_id' => $player->id, 'old_hc' => '3N', 'new_hc' => '3A', 'approved_at' => '2026-09-13 23:59:59']);
    expect($matches[0]->fresh()->player1_hc)->toBe(Handicap::ThreeN);
    expect($matches[1]->fresh()->player1_hc)->toBe(Handicap::ThreeA);
    expect($manual->fresh()->player1_hc)->toBe(Handicap::ThreeA);
    expect($matches[0]->fresh()->player1_score)->toBeNull();
    expect($player->fresh()->handicap)->toBe(Handicap::ThreeA);
});

test('correction updates the existing imported transition instead of duplicating its history', function () {
    $actor = User::factory()->create(['role' => UserRole::Superadmin]);
    $player = User::factory()->create(['handicap' => Handicap::ThreeN]);
    $event = FunGame::create(['title' => 'FG 19', 'event_date' => '2026-09-27', 'status' => FunGameStatus::Completed]);
    $event->forceFill(['source_key' => 'archive:event:19'])->save();
    $history = HcHistory::create(['user_id' => $player->id, 'old_hc' => '3B', 'new_hc' => '3N', 'approved_at' => '2026-10-04 00:00:00', 'reason' => 'Waktu belum tercatat']);
    $history->forceFill(['source_key' => 'archive:hc:satrio'])->save();

    $this->artisan('bozzbill:correct-hc-history', ['user' => $player->id, 'event' => $event->id, 'old' => '3B', 'new' => '3N', '--by' => $actor->id, '--apply' => true])->assertExitCode(0);

    $this->assertDatabaseCount('hc_histories', 1);
    $this->assertDatabaseHas('hc_histories', ['id' => $history->id, 'source_key' => 'archive:hc:satrio', 'approved_at' => '2026-09-27 23:59:59']);
});

test('correction refuses to overwrite a newer account HC', function () {
    $actor = User::factory()->create(['role' => UserRole::Superadmin]);
    $player = User::factory()->create(['handicap' => Handicap::ThreePlus]);
    $event = FunGame::create(['title' => 'FG 18', 'event_date' => '2026-09-13', 'status' => FunGameStatus::Completed]);
    $event->forceFill(['source_key' => 'archive:event:18'])->save();

    $this->artisan('bozzbill:correct-hc-history', ['user' => $player->id, 'event' => $event->id, 'old' => '3N', 'new' => '3A', '--by' => $actor->id, '--apply' => true])->expectsOutputToContain('HC akun saat ini berbeda')->assertExitCode(1);

    $this->assertDatabaseEmpty('hc_histories');
    expect($player->fresh()->handicap)->toBe(Handicap::ThreePlus);
});
