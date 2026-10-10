<?php

use App\Enums\FunGameStatus;
use App\Enums\UserRole;
use App\Models\FunGame;
use App\Models\MatchLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('all name selection screens render searchable fields with the expected form names', function (string $screen, int $expectedCount) {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $member = User::factory()->create(['name' => 'Rakha (Teman Azril)']);
    $event = FunGame::create(['title' => 'Event Aktif', 'event_date' => '2026-10-01', 'status' => FunGameStatus::Open, 'created_by' => $admin->id]);
    $match = MatchLog::create(['fun_game_id' => $event->id, 'player1_id' => $admin->id, 'player2_id' => $member->id,
        'winner_id' => $admin->id, 'player1_score' => 3, 'player2_score' => 1, 'player1_hc' => $admin->handicap, 'player2_hc' => $member->handicap]);
    $parameters = match ($screen) {
        'fun-games.matches.edit' => [$event, $match], 'fun-games.show' => [$event], 'roster' => ['tab' => 'roster'], default => [],
    };

    $response = $this->actingAs($admin)->get(route($screen, $parameters))->assertOk();

    $document = new DOMDocument;
    @$document->loadHTML($response->getContent());
    $xpath = new DOMXPath($document);
    expect($xpath->query('//input[@role="combobox"]')->length)->toBe($expectedCount);
    expect($xpath->query('//select[@name="player1_id" or @name="player2_id" or @name="winner_id" or @name="champion_id" or @name="runner_up_id" or @name="third_place_id"]')->length)->toBe(0);
    $response->assertSee('Rakha (Teman Azril)');
})->with([['fun-games.quick-logger', 2], ['fun-games.matches.edit', 3], ['fun-games.show', 3], ['roster', 1]]);

test('untrusted names remain escaped in search results and roster markup', function () {
    $member = User::factory()->create(['name' => '<script>alert(1)</script>']);

    $response = $this->actingAs($member)->get(route('roster', ['tab' => 'roster', 'search' => '<script>alert(1)</script>']))->assertOk();

    $response->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
    $response->assertSee('role="combobox"', false);
});
