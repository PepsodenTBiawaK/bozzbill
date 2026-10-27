<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('an administrator issues a hashed one-time code that expires after twenty four hours', function () {
    $this->travelTo('2026-10-27 12:00:00');
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $member = User::factory()->create(['password' => null]);

    $response = $this->actingAs($admin)->post(route('roster.activation-code', $member))
        ->assertRedirect(route('roster.show', $member))->assertSessionHasNoErrors();

    $code = session('issued_activation_code.code');
    expect($code)->toMatch('/^[0-9]{8}$/');
    expect(Hash::check($code, $member->fresh()->activation_code_hash))->toBeTrue();
    expect($member->fresh()->activation_code_expires_at->toDateTimeString())->toBe('2026-10-28 12:00:00');
    expect($member->fresh()->toArray())->not->toHaveKey('activation_code_hash');
    $this->get(route('roster.show', $member))->assertOk()->assertSee($code)->assertHeader('Cache-Control', 'no-store, private');
    $this->get(route('roster.show', $member))->assertOk()->assertDontSee($code);
});

test('only an authorized administrator can issue a code for an inactive target', function (UserRole $actorRole, UserRole $targetRole, bool $allowed) {
    $actor = User::factory()->create(['role' => $actorRole]);
    $target = User::factory()->create(['role' => $targetRole, 'password' => null]);

    $response = $this->actingAs($actor)->post(route('roster.activation-code', $target));

    if ($allowed) {
        $response->assertRedirect(route('roster.show', $target))->assertSessionHasNoErrors();
        expect($target->fresh()->activation_code_hash)->not->toBeNull();
    } else {
        $response->assertForbidden();
        expect($target->fresh()->activation_code_hash)->toBeNull();
    }
})->with([
    [UserRole::Member, UserRole::Member, false],
    [UserRole::Admin, UserRole::Admin, false],
    [UserRole::Admin, UserRole::Superadmin, false],
    [UserRole::Superadmin, UserRole::Admin, true],
]);

test('guests cannot issue codes and active accounts cannot receive activation codes', function () {
    $member = User::factory()->create(['is_active' => true]);
    $this->post(route('roster.activation-code', $member))->assertRedirect(route('login'));
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $this->actingAs($admin)->post(route('roster.activation-code', $member))->assertSessionHasErrors('activation_code');

    expect($member->fresh()->activation_code_hash)->toBeNull();
});

test('knowing a phone alone never reveals inactive member details or activates the account', function () {
    $member = User::factory()->awaitingActivation()->create(['name' => 'Private Member']);

    $this->postJson(route('activation.check'), ['phone' => $member->phone])
        ->assertUnprocessable()->assertJsonValidationErrors('activation_code')->assertDontSee('Private Member');
    $this->post(route('activate.post'), ['phone' => $member->phone, 'password' => 'new-password', 'password_confirmation' => 'new-password'])
        ->assertSessionHasErrors('activation_code');

    $this->assertGuest();
    $this->assertDatabaseHas('users', ['id' => $member->id, 'is_active' => false, 'password' => null]);
});

test('invalid expired and replacement codes cannot activate or expose inactive accounts', function (string $condition) {
    $this->freezeTime();
    $member = User::factory()->awaitingActivation()->create();
    if ($condition === 'expired') {
        $this->travel(25)->hours();
    } elseif ($condition === 'replaced') {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $this->actingAs($admin)->post(route('roster.activation-code', $member))->assertSessionHasNoErrors();
        $this->post(route('logout'));
        Auth::forgetGuards();
    }
    $code = $condition === 'wrong' ? '87654321' : '12345678';

    $this->postJson(route('activation.check'), ['phone' => $member->phone, 'activation_code' => $code])
        ->assertUnprocessable()->assertJsonValidationErrors('activation_code')->assertJsonMissingPath('user');
    $this->post(route('activate.post'), ['phone' => $member->phone, 'activation_code' => $code, 'password' => 'new-password', 'password_confirmation' => 'new-password'])
        ->assertSessionHasErrors('activation_code');

    $this->assertGuest();
    expect($member->fresh()->is_active)->toBeFalse();
})->with(['wrong', 'expired', 'replaced']);

test('a verified activation consumes its code and never flashes it after a validation error', function () {
    $member = User::factory()->awaitingActivation()->create();
    $payload = ['phone' => $member->phone, 'activation_code' => '12345678', 'password' => 'new-password', 'password_confirmation' => 'new-password'];
    $this->postJson(route('activation.check'), $payload)->assertOk()->assertJsonPath('user.name', $member->name);
    $this->post(route('activate.post'), [...$payload, 'password_confirmation' => 'mismatch'])->assertSessionHasErrors('password');
    expect(session()->getOldInput())->not->toHaveKeys(['activation_code', 'password', 'password_confirmation']);

    $this->post(route('activate.post'), $payload)->assertRedirect(route('home'))->assertSessionHasNoErrors();

    $this->assertAuthenticatedAs($member);
    expect($member->fresh()->activation_code_hash)->toBeNull();
    expect($member->fresh()->activation_code_expires_at)->toBeNull();
    $this->post(route('logout'));
    $this->post(route('activate.post'), [...$payload, 'password' => 'other-password', 'password_confirmation' => 'other-password'])->assertSessionHasErrors('password');
    $this->assertGuest();
    expect(Hash::check('new-password', $member->fresh()->password))->toBeTrue();
});

test('changing a member phone invalidates the activation code issued for the old number', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $member = User::factory()->awaitingActivation()->create();

    $this->actingAs($admin)->put(route('roster.phone', $member), ['phone' => '081234567899'])
        ->assertRedirect(route('roster.show', $member))->assertSessionHasNoErrors();

    expect($member->fresh()->activation_code_hash)->toBeNull();
    expect($member->fresh()->activation_code_expires_at)->toBeNull();
});
