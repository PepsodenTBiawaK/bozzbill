<?php

use App\Enums\FunGameStatus;
use App\Enums\UserRole;
use App\Models\FunGame;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('administrators edit event titles and descriptions including completed and cancelled histories', function (UserRole $role, FunGameStatus $status) {
    $actor = User::factory()->create(['role' => $role]);
    $event = FunGame::create(['title' => 'Judul Lama', 'notes' => 'Catatan lama', 'event_date' => '2026-10-01', 'status' => $status]);

    $this->actingAs($actor)->put(route('fun-games.update', $event), ['title' => 'Fun Game Bozz #22', 'notes' => 'Double di Tebet'])->assertRedirect(route('fun-games.show', $event))->assertSessionHasNoErrors();

    $this->assertDatabaseHas('fun_games', ['id' => $event->id, 'title' => 'Fun Game Bozz #22', 'notes' => 'Double di Tebet', 'status' => $status->value, 'event_date' => '2026-10-01 00:00:00']);
})->with([UserRole::Admin, UserRole::Superadmin])->with([FunGameStatus::Open, FunGameStatus::Completed, FunGameStatus::Cancelled]);

test('clearing an event description preserves its other metadata', function () {
    $actor = User::factory()->create(['role' => UserRole::Admin]);
    $event = FunGame::create(['title' => 'Fun Game', 'notes' => 'Catatan lama', 'event_date' => '2026-10-01', 'status' => FunGameStatus::Completed]);

    $this->actingAs($actor)->put(route('fun-games.update', $event), ['title' => 'Fun Game', 'notes' => ''])->assertSessionHasNoErrors();

    $this->assertDatabaseHas('fun_games', ['id' => $event->id, 'title' => 'Fun Game', 'notes' => null]);
});

test('editing title and description cannot reopen an event or alter its format date or champions', function () {
    $actor = User::factory()->create(['role' => UserRole::Admin]);
    $champion = User::factory()->create();
    $replacement = User::factory()->create();
    $event = FunGame::create(['title' => 'Final Lama', 'event_date' => '2026-10-01', 'status' => FunGameStatus::Completed, 'champion_id' => $champion->id, 'created_by' => $actor->id]);
    $original = $event->fresh()->getAttributes();

    $this->actingAs($actor)->put(route('fun-games.update', $event), [
        'title' => 'Final Bozz', 'notes' => 'Deskripsi diperbaiki', 'event_date' => '2027-01-01',
        'status' => 'open', 'format' => 'double', 'champion_id' => $replacement->id,
        'runner_up_id' => $replacement->id, 'third_place_id' => $replacement->id,
        'created_by' => $replacement->id, 'source_key' => 'tampered', 'match_records_complete' => false,
    ])->assertSessionHasNoErrors();

    $updated = $event->fresh()->getAttributes();
    foreach (['event_date', 'status', 'format', 'champion_id', 'runner_up_id', 'third_place_id', 'created_by', 'source_key', 'match_records_complete'] as $field) {
        expect($updated[$field])->toBe($original[$field]);
    }
    $this->assertDatabaseHas('fun_games', ['id' => $event->id, 'title' => 'Final Bozz', 'notes' => 'Deskripsi diperbaiki']);
});

test('metadata validation returns 422 without changing the stored history', function (array $payload, string $field) {
    $actor = User::factory()->create(['role' => UserRole::Admin]);
    $event = FunGame::create(['title' => 'Histori Aman', 'notes' => 'Tetap', 'event_date' => '2026-10-01', 'status' => FunGameStatus::Completed]);
    $original = $event->fresh()->getAttributes();

    $this->actingAs($actor)->putJson(route('fun-games.update', $event), $payload)->assertUnprocessable()->assertJsonValidationErrors($field);

    expect($event->fresh()->getAttributes())->toBe($original);
})->with([
    'title required' => [['notes' => 'Catatan'], 'title'], 'blank title' => [['title' => '   '], 'title'],
    'title too long' => [['title' => str_repeat('A', 151)], 'title'], 'title must be text' => [['title' => ['Judul']], 'title'],
    'description must be text' => [['title' => 'Valid', 'notes' => ['Catatan']], 'notes'],
]);

test('a title at the 150 character boundary can be saved', function () {
    $actor = User::factory()->create(['role' => UserRole::Superadmin]);
    $event = FunGame::create(['title' => 'Histori', 'event_date' => '2026-10-01', 'status' => FunGameStatus::Completed]);
    $title = str_repeat('A', 150);

    $this->actingAs($actor)->put(route('fun-games.update', $event), ['title' => $title])->assertSessionHasNoErrors();

    $this->assertDatabaseHas('fun_games', ['id' => $event->id, 'title' => $title]);
});

test('guests must sign in before editing fun game metadata', function () {
    $event = FunGame::create(['title' => 'Histori', 'notes' => 'Aman', 'event_date' => '2026-10-01', 'status' => FunGameStatus::Completed]);
    $original = $event->fresh()->getAttributes();

    $this->put(route('fun-games.update', $event), ['title' => 'Berubah', 'notes' => 'Berubah'])->assertRedirect(route('login'));

    expect($event->fresh()->getAttributes())->toBe($original);
});

test('ordinary members cannot edit fun game metadata', function () {
    $member = User::factory()->create();
    $event = FunGame::create(['title' => 'Histori', 'notes' => 'Aman', 'event_date' => '2026-10-01', 'status' => FunGameStatus::Completed]);
    $original = $event->fresh()->getAttributes();

    $this->actingAs($member)->put(route('fun-games.update', $event), ['title' => 'Berubah', 'notes' => 'Berubah'])->assertForbidden();

    expect($event->fresh()->getAttributes())->toBe($original);
});

test('edited titles and descriptions are escaped when members view the history', function () {
    $actor = User::factory()->create(['role' => UserRole::Admin]);
    $event = FunGame::create(['title' => 'Histori', 'event_date' => '2026-10-01', 'status' => FunGameStatus::Completed]);
    $title = '<script>alert("judul")</script>';
    $notes = '<img src=x onerror=alert("catatan")>';
    $this->actingAs($actor)->put(route('fun-games.update', $event), ['title' => $title, 'notes' => $notes])->assertSessionHasNoErrors();

    $this->get(route('fun-games.show', $event))->assertOk()->assertSee($title)->assertSee($notes)->assertDontSee($title, false)->assertDontSee($notes, false);
});
