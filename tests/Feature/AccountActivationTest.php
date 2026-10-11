<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('new activation starts a session that can access home on the next request', function () {
    $member = User::factory()->create(['phone' => '081234567890', 'password' => null]);
    $this->get(route('login', ['tab' => 'activation']));
    $previousSessionId = session()->getId();

    $this->post(route('activate.post'), [
        'phone' => '+62 812-3456-7890',
        'password' => 'member-password',
        'password_confirmation' => 'member-password',
        'role' => UserRole::Superadmin->value,
    ])->assertRedirect(route('home'))->assertSessionHasNoErrors()->assertSessionHas('status_success');

    $this->assertAuthenticatedAs($member);
    expect(session()->getId())->not->toBe($previousSessionId);
    expect(Hash::check('member-password', $member->fresh()->password))->toBeTrue();
    $this->assertDatabaseHas('users', ['id' => $member->id, 'is_active' => true, 'role' => UserRole::Member->value]);
    Auth::forgetGuards();
    $this->get(route('home'))->assertOk();
    $this->assertAuthenticatedAs($member);
});

test('checking an active account returns its normalized phone for the login tab', function () {
    User::factory()->create(['phone' => '081234567890', 'is_active' => true]);

    $this->postJson(route('activation.check'), ['phone' => '+62 812-3456-7890'])
        ->assertOk()->assertJson([
            'status' => 'already_active',
            'phone' => '081234567890',
            'message' => 'Akun Anda sudah aktif. Silakan masuk, Bozz, dengan password Anda.',
        ]);
    $this->assertGuest();
});

test('an active account can finish a repeated activation with its existing password', function () {
    $member = User::factory()->create(['phone' => '081234567890', 'is_active' => true]);
    $previousAttributes = $member->fresh()->getAttributes();

    $this->post(route('activate.post'), [
        'phone' => '081234567890',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertRedirect(route('home'))->assertSessionHasNoErrors();

    $this->assertAuthenticatedAs($member);
    expect($member->fresh()->getAttributes())->toBe($previousAttributes);
});

test('an active account rejects a different activation password and opens login with its phone', function () {
    $member = User::factory()->create(['phone' => '081234567890', 'is_active' => true]);
    $previousAttributes = $member->fresh()->getAttributes();

    $this->post(route('activate.post'), [
        'phone' => '+62 812-3456-7890',
        'password' => 'different-password',
        'password_confirmation' => 'different-password',
    ])->assertRedirect(route('login', ['tab' => 'login', 'phone' => '081234567890']))
        ->assertSessionHas('status_info')
        ->assertSessionHasErrors(['password' => 'Password yang Anda masukkan tidak cocok dengan password akun Anda.']);

    $this->assertGuest();
    expect($member->fresh()->getAttributes())->toBe($previousAttributes);
    $this->get(route('login', ['tab' => 'login', 'phone' => '081234567890']))
        ->assertOk()->assertViewHas('initialPhone', '081234567890')->assertViewHas('initialTab', 'login');
});

test('a stale activation form cannot replace a password after activation has completed', function () {
    $member = User::factory()->create(['phone' => '081234567890', 'password' => null]);
    $this->postJson(route('activation.check'), ['phone' => $member->phone])
        ->assertOk()->assertJsonPath('status', 'ready');
    $this->post(route('activate.post'), [
        'phone' => $member->phone,
        'password' => 'first-password',
        'password_confirmation' => 'first-password',
    ])->assertRedirect(route('home'));
    $this->post(route('logout'))->assertRedirect(route('login'));
    $savedPassword = $member->fresh()->password;

    $this->post(route('activate.post'), [
        'phone' => $member->phone,
        'password' => 'second-password',
        'password_confirmation' => 'second-password',
    ])->assertRedirect(route('login', ['tab' => 'login', 'phone' => $member->phone]))
        ->assertSessionHasErrors('password');

    $this->assertGuest();
    expect($member->fresh()->password)->toBe($savedPassword);
    expect($member->fresh()->is_active)->toBeTrue();
});

test('activation with mismatched confirmation keeps the account inactive', function () {
    $member = User::factory()->create(['password' => null]);

    $this->from(route('login', ['tab' => 'activation']))->post(route('activate.post'), [
        'phone' => $member->phone,
        'password' => 'first-password',
        'password_confirmation' => 'different-password',
    ])->assertSessionHasErrors(['password' => 'Konfirmasi password tidak cocok.']);

    $this->assertGuest();
    $this->assertDatabaseHas('users', ['id' => $member->id, 'is_active' => false, 'password' => null]);
});

test('activation cannot create an account for an unregistered phone', function () {
    $this->post(route('activate.post'), [
        'phone' => '081234567890',
        'password' => 'member-password',
        'password_confirmation' => 'member-password',
    ])->assertRedirect(route('login', ['tab' => 'activation', 'phone' => '081234567890']))
        ->assertSessionHasErrors('phone');

    $this->assertGuest();
    $this->assertDatabaseCount('users', 0);
});
