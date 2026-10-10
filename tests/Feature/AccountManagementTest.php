<?php

use App\Enums\Handicap;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('only superadmins can change account roles', function (UserRole $actorRole) {
    $actor = User::factory()->create(['role' => $actorRole, 'is_active' => true]);
    $target = User::factory()->create(['role' => UserRole::Member]);

    $this->actingAs($actor)->put(route('roster.role', $target), ['role' => 'admin'])->assertForbidden();
    expect($target->fresh()->role)->toBe(UserRole::Member);
    $this->assertDatabaseEmpty('account_changes');
})->with([UserRole::Member, UserRole::Admin]);

test('superadmins can promote and demote an account without changing player identity', function () {
    $owner = User::factory()->create(['role' => UserRole::Superadmin, 'is_active' => true]);
    $target = User::factory()->create(['handicap' => Handicap::ThreeA, 'is_active' => true]);
    $original = $target->only(['id', 'name', 'phone', 'password', 'handicap', 'is_active']);
    config(['session.driver' => 'database']);
    DB::table('sessions')->insert(['id' => 'target-session', 'user_id' => $target->id, 'payload' => '', 'last_activity' => time()]);

    $this->actingAs($owner)->put(route('roster.role', $target), ['role' => 'admin', 'handicap' => '3+'])->assertRedirect(route('roster.show', $target));
    expect($target->fresh()->role)->toBe(UserRole::Admin);
    expect($target->fresh()->only(array_keys($original)))->toBe($original);
    $this->assertDatabaseMissing('sessions', ['id' => 'target-session']);
    $this->assertDatabaseHas('account_changes', ['user_id' => $target->id, 'field' => 'role', 'old_value' => 'member', 'new_value' => 'admin', 'changed_by' => $owner->id]);
    $this->actingAs($owner)->get(route('roster'))->assertSee(route('roster.show', $target), false);
    $this->actingAs($owner)->get(route('roster.show', $target))->assertOk()->assertSee('Jabatan Akun');

    $this->actingAs($owner)->put(route('roster.role', $target), ['role' => 'member'])->assertRedirect();
    expect($target->fresh()->role)->toBe(UserRole::Member);
    expect($target->fresh()->only(array_keys($original)))->toBe($original);
});

test('the superadmin role cannot be assigned or removed through roster requests', function () {
    $owner = User::factory()->create(['role' => UserRole::Superadmin]);
    $target = User::factory()->create();
    $this->actingAs($owner)->put(route('roster.role', $target), ['role' => 'superadmin'])->assertSessionHasErrors('role');
    $this->actingAs($owner)->put(route('roster.role', $owner), ['role' => 'member'])->assertForbidden();
    expect($owner->fresh()->isSuperadmin())->toBeTrue();
});

test('admins cannot alter another admin or superadmin account', function (UserRole $targetRole) {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $target = User::factory()->create(['role' => $targetRole, 'is_active' => true]);
    $original = $target->refresh()->getAttributes();

    $this->actingAs($admin)->put(route('roster.phone', $target), ['phone' => '089999999999'])->assertForbidden();
    $this->actingAs($admin)->post(route('roster.reset-password', $target), ['password' => 'secret123', 'password_confirmation' => 'secret123'])->assertForbidden();
    $this->actingAs($admin)->get(route('roster.show', $target))->assertOk()->assertDontSee('Ubah Nomor WhatsApp')->assertDontSee('Reset Password')->assertDontSee('Jabatan Akun');
    expect($target->fresh()->getAttributes())->toBe($original);
})->with([UserRole::Admin, UserRole::Superadmin]);

test('changing a member phone normalizes the login identity and records the change', function (UserRole $actorRole) {
    $actor = User::factory()->create(['role' => $actorRole]);
    $target = User::factory()->create(['phone' => '081234567890', 'is_active' => true]);
    $oldToken = $target->remember_token;
    $password = $target->password;

    $this->actingAs($actor)->put(route('roster.phone', $target), ['phone' => '+62 899-9999-9999', 'role' => 'superadmin'])->assertRedirect();
    expect($target->fresh()->phone)->toBe('089999999999');
    expect($target->fresh()->password)->toBe($password);
    expect($target->fresh()->role)->toBe(UserRole::Member);
    expect($target->fresh()->remember_token)->not->toBe($oldToken);
    $this->assertDatabaseHas('account_changes', ['user_id' => $target->id, 'field' => 'phone', 'old_value' => '081234567890', 'new_value' => '089999999999']);
    $this->post(route('logout'));
    $this->post(route('login.post'), ['phone' => '081234567890', 'password' => 'password'])->assertSessionHasErrors('phone');
    $this->post(route('login.post'), ['phone' => '+6289999999999', 'password' => 'password'])->assertRedirect(route('home'));
    $this->assertAuthenticatedAs($target);
})->with([UserRole::Admin, UserRole::Superadmin]);

test('phone edits reject duplicate normalized numbers and malformed input', function (mixed $phone) {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $target = User::factory()->create();
    User::factory()->create(['phone' => '081234567890']);
    $original = $target->phone;
    $this->actingAs($admin)->put(route('roster.phone', $target), ['phone' => $phone])->assertSessionHasErrors('phone');
    expect($target->fresh()->phone)->toBe($original);
    $this->assertDatabaseEmpty('account_changes');
})->with(['+6281234567890', '', '123', ['phone']]);

test('members and guests cannot edit roster phones or roles', function () {
    $member = User::factory()->create();
    $target = User::factory()->create();
    $this->put(route('roster.phone', $target), ['phone' => '089999999999'])->assertRedirect(route('login'));
    $this->put(route('roster.role', $target), ['role' => 'admin'])->assertRedirect(route('login'));
    $this->actingAs($member)->put(route('roster.phone', $target), ['phone' => '089999999999'])->assertForbidden();
});

test('the existing owner bootstrap preserves credentials and cannot create a second owner', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin, 'is_active' => true]);
    $other = User::factory()->create(['role' => UserRole::Admin, 'is_active' => true]);
    $original = $admin->only(['id', 'phone', 'password', 'handicap', 'is_active']);
    $this->artisan('bozzbill:set-superadmin', ['user' => $admin->id])->assertSuccessful();
    $this->artisan('bozzbill:set-superadmin', ['user' => $admin->id])->assertSuccessful();
    $this->artisan('bozzbill:set-superadmin', ['user' => $other->id])->assertFailed();
    expect($admin->fresh()->isSuperadmin())->toBeTrue();
    expect($admin->fresh()->only(array_keys($original)))->toBe($original);
    expect($other->fresh()->role)->toBe(UserRole::Admin);
    $this->assertDatabaseCount('account_changes', 1);
});

test('superadmin uses the same operational screens as admin', function () {
    $owner = User::factory()->create(['role' => UserRole::Superadmin]);
    $this->actingAs($owner)->get(route('dashboard'))->assertOk();
    $this->actingAs($owner)->get(route('evaluating-hc.index'))->assertOk();
    $this->actingAs($owner)->get(route('roster'))->assertSee('Tambah Member');
});

test('superadmin can manage an admin account but uses the profile for their own password', function () {
    $owner = User::factory()->create(['role' => UserRole::Superadmin, 'is_active' => true]);
    $admin = User::factory()->create(['role' => UserRole::Admin, 'is_active' => true]);
    $this->actingAs($owner)->put(route('roster.phone', $admin), ['phone' => '089999999999'])->assertRedirect();
    $this->post(route('roster.reset-password', $admin), ['password' => 'newsecret123', 'password_confirmation' => 'newsecret123'])->assertRedirect();
    expect(Hash::check('newsecret123', $admin->fresh()->password))->toBeTrue();
    $this->post(route('roster.reset-password', $owner), ['password' => 'newsecret123', 'password_confirmation' => 'newsecret123'])->assertForbidden();
});

test('changing the superadmin own phone ends the old authenticated session', function () {
    $owner = User::factory()->create(['phone' => '081111111111', 'role' => UserRole::Superadmin, 'is_active' => true]);
    $this->actingAs($owner)->put(route('roster.phone', $owner), ['phone' => '+6289999999999'])->assertRedirect(route('login'));
    $this->assertGuest();
    $this->post(route('login.post'), ['phone' => '089999999999', 'password' => 'password'])->assertRedirect(route('home'));
    $this->assertAuthenticatedAs($owner->fresh());
});

test('submitting an unchanged phone does not rotate sessions or add audit records', function () {
    $owner = User::factory()->create(['phone' => '081111111111', 'role' => UserRole::Superadmin, 'is_active' => true]);
    $token = $owner->remember_token;
    $this->actingAs($owner)->put(route('roster.phone', $owner), ['phone' => '+6281111111111'])->assertRedirect(route('roster.show', $owner));
    $this->assertAuthenticatedAs($owner);
    expect($owner->fresh()->remember_token)->toBe($token);
    $this->assertDatabaseEmpty('account_changes');
});
