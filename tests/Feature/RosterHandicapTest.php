<?php

use App\Enums\Handicap;
use App\Enums\UserRole;
use App\Models\HcHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guests cannot change a roster handicap', function () {
    $target = User::factory()->create(['handicap' => Handicap::ThreeB]);
    $this->put(route('roster.handicap', $target), ['handicap' => '3N', 'reason' => 'Koreksi.'])->assertRedirect(route('login'));
    expect($target->fresh()->handicap)->toBe(Handicap::ThreeB);
    $this->assertDatabaseCount('hc_histories', 0);
});

test('administrators can set roster handicap and record who changed it', function (UserRole $role) {
    $actor = User::factory()->create(['role' => $role]);
    $target = User::factory()->create(['handicap' => Handicap::ThreeB]);

    $this->actingAs($actor)->put(route('roster.handicap', $target), ['handicap' => '3N', 'reason' => 'Fundamental sudah berkembang.'])
        ->assertRedirect(route('roster.show', $target))->assertSessionHasNoErrors();

    expect($target->fresh()->handicap)->toBe(Handicap::ThreeN);
    $this->assertDatabaseHas('hc_histories', ['user_id' => $target->id, 'old_hc' => '3B', 'new_hc' => '3N', 'changed_by' => $actor->id, 'reason' => 'Fundamental sudah berkembang.']);
    expect(HcHistory::first()->approved_at)->not->toBeNull();
    $this->get(route('roster.show', $target))->assertOk()->assertSee('Atur HC Pemain');
})->with([UserRole::Admin, UserRole::Superadmin]);

test('administrators can change their own handicap without changing their role or credentials', function (UserRole $role) {
    $actor = User::factory()->create(['role' => $role, 'handicap' => Handicap::ThreeB]);
    $password = $actor->password;
    $phone = $actor->phone;

    $this->actingAs($actor)->get(route('roster.show', $actor))->assertOk()->assertSee('Simpan HC');
    $this->put(route('roster.handicap', $actor), ['handicap' => '3A', 'reason' => 'Koreksi HC sendiri.'])->assertSessionHasNoErrors();

    expect($actor->fresh()->handicap)->toBe(Handicap::ThreeA);
    expect($actor->fresh()->role)->toBe($role);
    expect($actor->fresh()->password)->toBe($password);
    expect($actor->fresh()->phone)->toBe($phone);
    $this->assertDatabaseHas('hc_histories', ['user_id' => $actor->id, 'changed_by' => $actor->id]);
})->with([UserRole::Admin, UserRole::Superadmin]);

test('members cannot set their own or another players handicap', function (bool $self) {
    $actor = User::factory()->create(['handicap' => Handicap::ThreeB]);
    $target = $self ? $actor : User::factory()->create(['handicap' => Handicap::ThreeB]);
    $this->actingAs($actor)->put(route('roster.handicap', $target), ['handicap' => '3+', 'reason' => 'Koreksi.'])->assertForbidden();
    $this->get(route('roster.show', $target))->assertDontSee('Simpan HC');
    expect($target->fresh()->handicap)->toBe(Handicap::ThreeB);
    $this->assertDatabaseCount('hc_histories', 0);
})->with([true, false]);

test('handicap updates require a valid level and an explanation', function (array $payload, string $field) {
    $actor = User::factory()->create(['role' => UserRole::Admin, 'handicap' => Handicap::ThreeB]);
    $this->actingAs($actor)->putJson(route('roster.handicap', $actor), $payload)->assertUnprocessable()->assertJsonValidationErrors($field);
    expect($actor->fresh()->handicap)->toBe(Handicap::ThreeB);
    $this->assertDatabaseCount('hc_histories', 0);
})->with([
    'invalid HC' => [['handicap' => '4A', 'reason' => 'Koreksi.'], 'handicap'],
    'missing explanation' => [['handicap' => '3N'], 'reason'],
    'blank explanation' => [['handicap' => '3N', 'reason' => '   '], 'reason'],
]);

test('saving the same handicap does not create a false history entry', function () {
    $actor = User::factory()->create(['role' => UserRole::Superadmin, 'handicap' => Handicap::ThreeB]);
    $this->actingAs($actor)->put(route('roster.handicap', $actor), ['handicap' => '3B', 'reason' => 'Sudah sesuai.'])->assertSessionHasNoErrors();
    $this->assertDatabaseCount('hc_histories', 0);
});

test('manual handicap changes preserve historical match snapshots', function () {
    $this->seed();
    $actor = User::where('role', UserRole::Admin)->firstOrFail();
    $target = User::where('phone', '081111111111')->firstOrFail();
    $original = $target->allMatches()->get()->map(fn ($match) => $match->getAttributes())->all();

    $this->actingAs($actor)->put(route('roster.handicap', $target), ['handicap' => '3B', 'reason' => 'Koreksi hasil evaluasi.'])->assertSessionHasNoErrors();

    expect($target->fresh()->handicap)->toBe(Handicap::ThreeB);
    expect($target->allMatches()->get()->map(fn ($match) => $match->getAttributes())->all())->toBe($original);
});
