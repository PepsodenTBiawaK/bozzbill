<?php

use App\Enums\FunGameStatus;
use App\Enums\UserRole;
use App\Models\FunGame;
use App\Models\FunGameTeam;
use App\Models\MatchLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/** @return array{admin: User, event: FunGame, first: FunGameTeam, second: FunGameTeam} */
function doubleFunGameUiFixture(): array
{
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $event = FunGame::create(['title' => 'Fun Game Double', 'event_date' => '2026-10-13', 'format' => 'double', 'status' => FunGameStatus::Open, 'created_by' => $admin->id]);
    $players = User::factory()->count(4)->sequence(['name' => 'Fadil'], ['name' => 'Hafiz'], ['name' => 'Andrew'], ['name' => 'Fitrah'])->create();
    $first = FunGameTeam::factory()->create(['fun_game_id' => $event->id, 'player1_id' => $players[0]->id, 'player2_id' => $players[1]->id]);
    $second = FunGameTeam::factory()->create(['fun_game_id' => $event->id, 'player1_id' => $players[2]->id, 'player2_id' => $players[3]->id]);

    return compact('admin', 'event', 'first', 'second');
}

test('double match input and editing offer searchable pairs and a score-derived team winner', function (string $screen) {
    ['admin' => $admin, 'event' => $event, 'first' => $first, 'second' => $second] = doubleFunGameUiFixture();
    $parameters = [];
    if ($screen === 'fun-games.matches.edit') {
        $match = MatchLog::create(['fun_game_id' => $event->id, 'team1_id' => $first->id, 'team2_id' => $second->id, 'winner_team_id' => $first->id, 'player1_score' => 3, 'player2_score' => 1, 'stage' => 'Final', 'recorded_by' => $admin->id]);
        $parameters = [$event, $match];
    }

    $response = $this->actingAs($admin)->get(route($screen, $parameters))->assertOk();

    $document = new DOMDocument;
    @$document->loadHTML($response->getContent());
    $xpath = new DOMXPath($document);
    expect($xpath->query('//input[@role="combobox"]')->length)->toBe(2);
    expect($xpath->query('//input[@name="team1_id" or @name="team2_id"]')->length)->toBe(2);
    expect($xpath->query('//input[@name="winner_team_id"]')->length)->toBe(1);
    expect($xpath->query('//input[@name="player1_id" or @name="player2_id" or @name="winner_id"]')->length)->toBe(0);
    expect($xpath->query('//div[@x-model="p1Id"] | //div[@x-model="p2Id"]')->length)->toBe(2);
    $response->assertSee('Ketik nama salah satu anggota')->assertSee('Pemenang mengikuti skor');
})->with(['fun-games.quick-logger', 'fun-games.matches.edit']);

test('double detail presents pairs with unique searchable field ids and locks played pairs', function () {
    ['admin' => $admin, 'event' => $event, 'first' => $first, 'second' => $second] = doubleFunGameUiFixture();
    $idle = FunGameTeam::factory()->create(['fun_game_id' => $event->id]);
    MatchLog::create(['fun_game_id' => $event->id, 'team1_id' => $first->id, 'team2_id' => $second->id, 'winner_team_id' => $first->id, 'player1_score' => 3, 'player2_score' => 1, 'recorded_by' => $admin->id]);

    $response = $this->actingAs($admin)->get(route('fun-games.show', $event))->assertOk();

    $response->assertSee('Fadil &amp; Hafiz', false)->assertSee('Andrew &amp; Fitrah', false)->assertSee('Pasangan terkunci setelah bermain.');
    $document = new DOMDocument;
    @$document->loadHTML($response->getContent());
    $xpath = new DOMXPath($document);
    expect($xpath->query('//input[@name="champion_team_id"]')->length)->toBe(1);
    expect($xpath->query('//input[@name="runner_up_team_id" or @name="third_place_team_id"]')->length)->toBe(2);
    $ids = [];
    foreach ($xpath->query('//input[@role="combobox"]') as $input) {
        $ids[] = $input->getAttribute('id');
    }
    expect(count(array_unique($ids)))->toBe(count($ids));
    foreach ([$first, $second] as $team) {
        $action = route('fun-games.teams.update', [$event, $team]);
        expect($xpath->query('//form[@action="'.$action.'"]')->length)->toBe(0);
    }
    $idleAction = route('fun-games.teams.update', [$event, $idle]);
    expect($xpath->query('//form[@action="'.$idleAction.'"]/input[@name="_method" and @value="PUT"]')->length)->toBe(1);
});

test('completed and cancelled history exposes metadata editing to administrators while member views stay read only', function (UserRole $role, FunGameStatus $status) {
    ['admin' => $admin, 'event' => $event] = doubleFunGameUiFixture();
    $event->update(['status' => $status]);
    $actor = $role === UserRole::Admin ? $admin : User::factory()->create(['role' => $role]);

    $response = $this->actingAs($actor)->get(route('fun-games.show', $event))->assertOk();

    $document = new DOMDocument;
    @$document->loadHTML($response->getContent());
    $xpath = new DOMXPath($document);
    expect($xpath->query('//form[@id="edit-event-details"]')->length)->toBe($role === UserRole::Member ? 0 : 1);
    expect($xpath->query('//form[@id="new-event-team"]')->length)->toBe(0);
    if ($role !== UserRole::Member) {
        $response->assertSee('Edit Judul & Deskripsi');
        expect($xpath->query('//form[@id="edit-event-details"]/input[@name="_method" and @value="PUT"]')->length)->toBe(1);
    }
})->with([UserRole::Admin, UserRole::Superadmin, UserRole::Member])->with([FunGameStatus::Completed, FunGameStatus::Cancelled]);

test('double logger with insufficient pairs directs administrators to the existing event setup', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $event = FunGame::create(['title' => 'Double Baru', 'event_date' => '2026-10-13', 'format' => 'double', 'status' => FunGameStatus::Open, 'created_by' => $admin->id]);

    $this->actingAs($admin)->get(route('fun-games.quick-logger'))->assertOk()
        ->assertSee('Susun minimal dua pasangan')
        ->assertSee(route('fun-games.show', $event).'#event-teams', false);
});

test('untrusted pair names and editable event metadata render as text', function () {
    ['admin' => $admin, 'event' => $event, 'first' => $first] = doubleFunGameUiFixture();
    $name = '<script>alert(1)</script>';
    $first->player1->update(['name' => $name]);
    $event->update(['title' => '<script>alert(2)</script>', 'notes' => '<img src=x onerror=alert(3)>']);

    $response = $this->actingAs($admin)->get(route('fun-games.show', $event))->assertOk();

    $response->assertSee($name)->assertSee('<script>alert(2)</script>')->assertSee('<img src=x onerror=alert(3)>')
        ->assertDontSee($name, false)->assertDontSee('<script>alert(2)</script>', false)->assertDontSee('<img src=x onerror=alert(3)>', false);
});
