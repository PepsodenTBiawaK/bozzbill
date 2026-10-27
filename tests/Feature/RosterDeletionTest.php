<?php

use App\Enums\FunGameStatus;
use App\Enums\UserRole;
use App\Models\FunGame;
use App\Models\FunGameTeam;
use App\Models\MatchLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

/** @return array{owner: User, member: User, payload: array<string, string>} */
function rosterDeletionFixture(): array
{
    $owner = User::factory()->create(['role' => UserRole::Superadmin, 'is_active' => true]);
    $member = User::factory()->create(['name' => 'Anggota Uji', 'is_active' => true]);
    $payload = ['current_password' => 'password', 'confirmation_name' => $member->name, 'delete_confirmed' => '1'];

    return compact('owner', 'member', 'payload');
}

test('only superadmins can see and submit roster deletion', function (UserRole $role, bool $allowed) {
    ['owner' => $owner, 'member' => $member, 'payload' => $payload] = rosterDeletionFixture();
    $actor = $role === UserRole::Superadmin ? $owner : User::factory()->create(['role' => $role]);
    $page = $this->actingAs($actor)->get(route('roster.show', $member))->assertOk();
    $allowed ? $page->assertSee('Hapus Roster Anggota') : $page->assertDontSee('Hapus Roster Anggota');

    $response = $this->delete(route('roster.destroy', $member), $payload);

    if ($allowed) {
        $response->assertRedirect(route('roster', ['tab' => 'roster']))->assertSessionHasNoErrors();
        $this->assertSoftDeleted($member);
        $this->assertDatabaseHas('account_changes', ['user_id' => $member->id, 'changed_by' => $actor->id, 'field' => 'deleted_at']);
    } else {
        $response->assertForbidden();
        $this->assertNotSoftDeleted($member);
    }
})->with([[UserRole::Member, false], [UserRole::Admin, false], [UserRole::Superadmin, true]]);

test('guests cannot delete rosters and no superadmin account can be deleted', function () {
    ['owner' => $owner, 'member' => $member, 'payload' => $payload] = rosterDeletionFixture();
    $otherOwner = User::factory()->create(['role' => UserRole::Superadmin]);
    $this->deleteJson(route('roster.destroy', $member), $payload)->assertUnauthorized();

    $this->actingAs($owner)->deleteJson(route('roster.destroy', $owner), [...$payload, 'confirmation_name' => $owner->name])->assertForbidden();
    $this->deleteJson(route('roster.destroy', $otherOwner), [...$payload, 'confirmation_name' => $otherOwner->name])->assertForbidden();

    $this->assertNotSoftDeleted($owner);
    $this->assertNotSoftDeleted($otherOwner);
    $this->assertNotSoftDeleted($member);
});

test('deletion requires the current password the exact member name and explicit confirmation', function (array $changes, string $field) {
    ['owner' => $owner, 'member' => $member, 'payload' => $payload] = rosterDeletionFixture();
    $original = $member->fresh()->getAttributes();

    $this->actingAs($owner)->delete(route('roster.destroy', $member), [...$payload, ...$changes])->assertSessionHasErrors($field);

    expect($member->fresh()->getAttributes())->toBe($original);
    expect(session()->getOldInput())->not->toHaveKey('current_password');
    $this->assertDatabaseCount('account_changes', 0);
})->with([
    [['current_password' => ''], 'current_password'],
    [['current_password' => 'wrong-password'], 'current_password'],
    [['confirmation_name' => 'Wrong Person'], 'confirmation_name'],
    [['delete_confirmed' => '0'], 'delete_confirmed'],
]);

test('deletion checks the latest password and permission rather than a stale session model', function (string $changedField) {
    ['owner' => $owner, 'member' => $member, 'payload' => $payload] = rosterDeletionFixture();
    $this->actingAs($owner);
    DB::table('users')->where('id', $owner->id)->update($changedField === 'role' ? ['role' => 'admin'] : ['password' => Hash::make('new-password')]);

    $response = $this->deleteJson(route('roster.destroy', $member), $payload);

    if ($changedField === 'role') {
        $response->assertForbidden();
    } else {
        $response->assertUnprocessable()->assertJsonValidationErrors('current_password');
    }
    $this->assertNotSoftDeleted($member);
})->with(['role', 'password']);

test('password guessing on roster deletion is limited without deleting the member', function () {
    ['owner' => $owner, 'member' => $member, 'payload' => $payload] = rosterDeletionFixture();
    $this->actingAs($owner);
    for ($attempt = 0; $attempt < 5; $attempt++) {
        $this->deleteJson(route('roster.destroy', $member), [...$payload, 'current_password' => 'incorrect'])->assertUnprocessable();
    }

    $this->deleteJson(route('roster.destroy', $member), $payload)->assertTooManyRequests();

    $this->assertNotSoftDeleted($member);
});

test('deletion preserves single match identity scores podium and opponent statistics', function () {
    ['owner' => $owner, 'member' => $member, 'payload' => $payload] = rosterDeletionFixture();
    $member->update(['role' => UserRole::Admin]);
    $opponent = User::factory()->create();
    $event = FunGame::create(['title' => 'Arsip Single', 'event_date' => today(), 'status' => FunGameStatus::Completed, 'created_by' => $member->id, 'champion_id' => $member->id]);
    $match = MatchLog::create(['fun_game_id' => $event->id, 'player1_id' => $member->id, 'player2_id' => $opponent->id, 'player1_score' => 3, 'player2_score' => 1, 'winner_id' => $member->id, 'recorded_by' => $member->id]);
    $original = $match->fresh()->getAttributes();

    $this->actingAs($owner)->delete(route('roster.destroy', $member), $payload)->assertSessionHasNoErrors();

    expect($match->fresh()->getAttributes())->toBe($original);
    expect($match->fresh()->player1->name)->toBe('Anggota Uji');
    expect($match->fresh()->recorder->name)->toBe('Anggota Uji');
    expect($event->fresh()->podiumName(1))->toBe('Anggota Uji');
    expect($event->fresh()->creator->name)->toBe('Anggota Uji');
    expect($opponent->totalMatchesCount())->toBe(1);
    $this->get(route('fun-games.show', $event))->assertOk()->assertSee('Anggota Uji');
    $this->get(route('roster.show', $member))->assertNotFound();
    $this->get(route('head-to-head'))->assertOk()->assertViewHas('players', fn ($players): bool => ! $players->contains('id', $member->id));
    $this->get(route('roster'))->assertOk()->assertViewHas('totalMembers', 2);
});

test('deleted identities cannot log in or activate and their phone is available to a new roster', function () {
    ['owner' => $owner, 'member' => $member, 'payload' => $payload] = rosterDeletionFixture();
    $phone = $member->phone;
    $token = $member->remember_token;
    $this->actingAs($owner)->delete(route('roster.destroy', $member), $payload)->assertSessionHasNoErrors();
    $archived = User::withTrashed()->findOrFail($member->id);
    expect($archived->phone)->toBeNull();
    expect($archived->password)->toBeNull();
    expect($archived->remember_token)->not->toBe($token);
    $this->post(route('members.store'), ['name' => 'Anggota Baru', 'phone' => $phone, 'handicap' => '3B'])->assertSessionHasNoErrors();
    expect(User::where('phone', $phone)->firstOrFail()->id)->not->toBe($member->id);
    $this->post(route('logout'));

    $this->post(route('login.post'), ['phone' => $phone, 'password' => 'password'])->assertRedirect(route('login', ['tab' => 'activation', 'phone' => $phone]));
    $this->postJson(route('activate.post'), ['phone' => $phone, 'password' => 'old-password', 'password_confirmation' => 'old-password'])->assertUnprocessable();
    $this->assertGuest();
});

test('deletion revokes existing database sessions and old remembered login cookies', function () {
    config(['session.driver' => 'database']);
    ['owner' => $owner, 'member' => $member, 'payload' => $payload] = rosterDeletionFixture();
    $cookieName = Auth::guard()->getRecallerName();
    $response = $this->post(route('login.post'), ['phone' => $member->phone, 'password' => 'password', 'remember' => '1'])->assertRedirect(route('home'));
    $oldCookie = $response->getCookie($cookieName);
    session()->invalidate();
    session()->save();
    Auth::forgetGuards();
    DB::table('sessions')->insert(['id' => 'removed-device', 'user_id' => $member->id, 'payload' => '', 'last_activity' => now()->getTimestamp()]);

    $this->actingAs($owner)->delete(route('roster.destroy', $member), $payload)->assertSessionHasNoErrors();

    $this->assertDatabaseMissing('sessions', ['id' => 'removed-device']);
    $this->post(route('logout'));
    Auth::forgetGuards();
    $this->withCookie($cookieName, $oldCookie->getValue())->get(route('home'))->assertRedirect(route('login'));
    $this->assertGuest();
});

test('deleted participants remain available only for editing their own historical single matches', function () {
    ['owner' => $owner, 'member' => $member, 'payload' => $payload] = rosterDeletionFixture();
    $opponent = User::factory()->create();
    $event = FunGame::create(['title' => 'Match Aktif', 'event_date' => today(), 'status' => FunGameStatus::Open, 'created_by' => $owner->id]);
    $values = ['player1_id' => $member->id, 'player2_id' => $opponent->id, 'player1_score' => 3, 'player2_score' => 1, 'winner_id' => $member->id];
    $match = MatchLog::create([...$values, 'fun_game_id' => $event->id, 'recorded_by' => $owner->id]);
    $this->actingAs($owner)->delete(route('roster.destroy', $member), $payload)->assertSessionHasNoErrors();

    $this->postJson(route('fun-games.matches.store', $event), $values)->assertUnprocessable()->assertJsonValidationErrors('player1_id');
    $this->get(route('fun-games.matches.edit', [$event, $match]))->assertOk()->assertViewHas('members', fn ($members): bool => $members->contains('id', $member->id));
    $this->put(route('fun-games.matches.update', [$event, $match]), [...$values, 'player2_score' => 2])->assertSessionHasNoErrors();
    $this->post(route('fun-games.close', $event), ['champion_id' => $member->id])->assertSessionHasNoErrors();
    expect($event->fresh()->podiumName(1))->toBe('Anggota Uji');
    $this->put(route('fun-games.matches.update', [$event, $match]), [
        ...$values, 'player2_score' => 0, 'current_password' => 'password', 'correction_reason' => 'Koreksi histori setelah anggota keluar.',
        'correction_confirmed' => '1', 'match_version' => $match->fresh()->correctionVersion(),
    ])->assertSessionHasNoErrors();
    expect($match->fresh()->player2_score)->toBe(0);
    $this->assertDatabaseCount('match_log_corrections', 1);
});

test('double history and podium retain deleted players while new matches reject their pairs', function () {
    ['owner' => $owner, 'member' => $member, 'payload' => $payload] = rosterDeletionFixture();
    $players = User::factory()->count(3)->create();
    $event = FunGame::create(['title' => 'Double Aktif', 'event_date' => today(), 'format' => 'double', 'status' => FunGameStatus::Open, 'created_by' => $owner->id]);
    $first = FunGameTeam::factory()->create(['fun_game_id' => $event->id, 'player1_id' => $member->id, 'player2_id' => $players[0]->id]);
    $second = FunGameTeam::factory()->create(['fun_game_id' => $event->id, 'player1_id' => $players[1]->id, 'player2_id' => $players[2]->id]);
    $values = ['team1_id' => $first->id, 'team2_id' => $second->id, 'winner_team_id' => $first->id, 'player1_score' => 3, 'player2_score' => 1];
    $match = MatchLog::create([...$values, 'fun_game_id' => $event->id, 'recorded_by' => $owner->id]);
    $original = $match->fresh()->getAttributes();
    $this->actingAs($owner)->delete(route('roster.destroy', $member), $payload)->assertSessionHasNoErrors();

    expect($match->fresh()->getAttributes())->toBe($original);
    expect($first->fresh()->displayName())->toContain('Anggota Uji');
    $this->get(route('fun-games.show', $event))->assertOk()->assertSee('Anggota Uji');
    $this->postJson(route('fun-games.matches.store', $event), $values)->assertUnprocessable()->assertJsonValidationErrors('team1_id');
    $this->put(route('fun-games.matches.update', [$event, $match]), [...$values, 'player2_score' => 2])->assertSessionHasNoErrors();
    $this->post(route('fun-games.close', $event), ['champion_team_id' => $first->id])->assertSessionHasNoErrors();
    expect($event->fresh()->podiumName(1))->toContain('Anggota Uji', $players[0]->name);
    $this->assertDatabaseHas('fun_game_podium', ['fun_game_id' => $event->id, 'user_id' => $member->id, 'position' => 1]);
});
