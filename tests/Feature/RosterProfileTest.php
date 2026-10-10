<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('guests are redirected to login when viewing a roster member', function () {
    $member = User::factory()->create([
        'phone' => '082222222222',
        'is_active' => true,
    ]);

    $response = $this->get(route('roster.show', $member));

    $response->assertRedirect(route('login'));
});

test('member profiles mask phone numbers and hide emails from members and admins', function (UserRole $viewerRole) {
    $viewer = User::factory()->create([
        'phone' => '081111111111',
        'role' => $viewerRole,
        'is_active' => true,
    ]);
    $member = User::factory()->create([
        'name' => 'Pemain Tujuan',
        'phone' => '082222222222',
        'email' => 'pemain-tujuan@example.com',
        'is_active' => true,
    ]);

    $response = $this->actingAs($viewer)->get(route('roster.show', $member));

    $response->assertOk()
        ->assertSee('Pemain Tujuan')
        ->assertSee('0822•••••222')
        ->assertDontSee($member->phone)
        ->assertDontSee($member->email)
        ->assertDontSee($viewer->phone)
        ->assertDontSee($viewer->email)
        ->assertSee('href="'.route('head-to-head', ['opponent_id' => $member->id]).'"', false);
})->with([
    'member viewer' => [UserRole::Member],
    'admin viewer' => [UserRole::Admin],
]);

test('a member profile displays the selected player statistics instead of the viewer statistics', function () {
    $this->seed();
    $viewer = User::where('phone', '083333333333')->firstOrFail();
    $member = User::where('phone', '081111111111')->firstOrFail();

    $response = $this->actingAs($viewer)->get(route('roster.show', $member));

    $response->assertOk()
        ->assertViewHas('member', fn (User $profileMember): bool => $profileMember->is($member))
        ->assertViewHas('stats', [
            'win_rate' => 68.0,
            'total_matches' => 25,
            'wins' => 17,
            'total_losses' => 8,
            'fun_games_count' => 4,
        ])
        ->assertSee('Apr Pinky')
        ->assertSee('68');
});

test('a member viewing their own roster profile is not offered a duel against themselves', function () {
    $member = User::factory()->create([
        'phone' => '082222222222',
        'is_active' => true,
    ]);

    $response = $this->actingAs($member)->get(route('roster.show', $member));

    $response->assertOk()
        ->assertDontSee('href="'.route('head-to-head', ['opponent_id' => $member->id]).'"', false);
});

test('regular members can view an unactivated member profile with masked contact details', function () {
    $viewer = User::factory()->create([
        'phone' => '081111111111',
        'is_active' => true,
    ]);
    $member = User::factory()->create([
        'phone' => '082222222222',
        'password' => null,
        'is_active' => false,
    ]);

    $response = $this->actingAs($viewer)->get(route('roster.show', $member));

    $response->assertOk()
        ->assertSee('Belum Aktivasi')
        ->assertSee($member->maskedPhone())
        ->assertDontSee($member->phone)
        ->assertDontSee($member->email)
        ->assertSee('href="'.route('head-to-head', ['opponent_id' => $member->id]).'"', false);
});

test('admins can view an inactive member profile without exposing contact details', function () {
    $admin = User::factory()->create([
        'phone' => '081111111111',
        'role' => UserRole::Admin,
        'is_active' => true,
    ]);
    $member = User::factory()->create([
        'name' => 'Member Belum Aktif',
        'phone' => '082222222222',
        'password' => null,
        'is_active' => false,
    ]);

    $response = $this->actingAs($admin)->get(route('roster.show', $member));

    $response->assertOk()
        ->assertSee('Member Belum Aktif')
        ->assertSee('0822•••••222')
        ->assertDontSee($member->phone)
        ->assertDontSee($member->email);
});

test('roster profiles include administrators as community players', function (UserRole $viewerRole) {
    $viewer = User::factory()->create([
        'phone' => '081111111111',
        'role' => $viewerRole,
        'is_active' => true,
    ]);
    $admin = User::factory()->create([
        'phone' => '082222222222',
        'role' => UserRole::Admin,
        'is_active' => true,
    ]);

    $response = $this->actingAs($viewer)->get(route('roster.show', $admin));

    $response->assertOk();
})->with([
    'member viewer' => [UserRole::Member],
    'admin viewer' => [UserRole::Admin],
]);

test('member names are escaped in the roster profile', function () {
    $viewer = User::factory()->create([
        'phone' => '081111111111',
        'is_active' => true,
    ]);
    $member = User::factory()->create([
        'name' => '<script>alert("profil")</script>',
        'phone' => '082222222222',
        'is_active' => true,
    ]);

    $response = $this->actingAs($viewer)->get(route('roster.show', $member));

    $response->assertOk()
        ->assertSee($member->name)
        ->assertDontSee($member->name, false);
});

test('regular members cannot see password reset controls on a member profile', function () {
    $viewer = User::factory()->create([
        'phone' => '081111111111',
        'is_active' => true,
    ]);
    $member = User::factory()->create([
        'phone' => '082222222222',
        'is_active' => true,
    ]);

    $response = $this->actingAs($viewer)->get(route('roster.show', $member));

    $response->assertOk()
        ->assertDontSee('Reset Password')
        ->assertDontSee(route('roster.reset-password', $member));
});

test('admins can see password reset controls on an active member profile', function () {
    $admin = User::factory()->create([
        'phone' => '081111111111',
        'role' => UserRole::Admin,
        'is_active' => true,
    ]);
    $member = User::factory()->create([
        'phone' => '082222222222',
        'is_active' => true,
    ]);

    $response = $this->actingAs($admin)->get(route('roster.show', $member));

    $response->assertOk()
        ->assertSee('Reset Password')
        ->assertSee('action="'.route('roster.reset-password', $member).'"', false);
});

test('roster player cards link to the selected member profile', function () {
    $viewer = User::factory()->create([
        'phone' => '081111111111',
        'is_active' => true,
    ]);
    $member = User::factory()->create([
        'phone' => '082222222222',
        'is_active' => true,
    ]);

    $response = $this->actingAs($viewer)->get(route('roster', ['tab' => 'roster']));

    $response->assertOk()
        ->assertSee('href="'.route('roster.show', $member).'"', false)
        ->assertDontSee($member->phone);
});

test('guests cannot reset a member password', function () {
    $member = User::factory()->create([
        'phone' => '082222222222',
        'is_active' => true,
    ]);
    $originalAttributes = $member->refresh()->getAttributes();

    $response = $this->post(route('roster.reset-password', $member), [
        'password' => 'baru12',
        'password_confirmation' => 'baru12',
    ]);

    $response->assertRedirect(route('login'));
    expect($member->fresh()->getAttributes())->toBe($originalAttributes);
});

test('regular members cannot reset another member password', function () {
    $viewer = User::factory()->create([
        'phone' => '081111111111',
        'is_active' => true,
    ]);
    $member = User::factory()->create([
        'phone' => '082222222222',
        'is_active' => true,
    ]);
    $originalAttributes = $member->refresh()->getAttributes();

    $response = $this->actingAs($viewer)->post(route('roster.reset-password', $member), [
        'password' => 'baru12',
        'password_confirmation' => 'baru12',
    ]);

    $response->assertForbidden();
    expect($member->fresh()->getAttributes())->toBe($originalAttributes);
});

test('admins cannot reset an admin password through the roster', function () {
    $viewer = User::factory()->create([
        'phone' => '081111111111',
        'role' => UserRole::Admin,
        'is_active' => true,
    ]);
    $admin = User::factory()->create([
        'phone' => '082222222222',
        'role' => UserRole::Admin,
        'is_active' => true,
    ]);
    $originalAttributes = $admin->refresh()->getAttributes();

    $response = $this->actingAs($viewer)->post(route('roster.reset-password', $admin), [
        'password' => 'baru12',
        'password_confirmation' => 'baru12',
    ]);

    $response->assertForbidden();
    expect($admin->fresh()->getAttributes())->toBe($originalAttributes);
});

test('admins cannot reset passwords for members who have not activated their account', function () {
    $admin = User::factory()->create([
        'phone' => '081111111111',
        'role' => UserRole::Admin,
        'is_active' => true,
    ]);
    $member = User::factory()->create([
        'phone' => '082222222222',
        'password' => null,
        'is_active' => false,
    ]);
    $originalAttributes = $member->refresh()->getAttributes();

    $response = $this->actingAs($admin)->postJson(route('roster.reset-password', $member), [
        'password' => 'baru12',
        'password_confirmation' => 'baru12',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors([
            'password' => 'Akun belum diaktifkan. Member perlu melakukan aktivasi akun terlebih dahulu.',
        ]);
    expect($member->fresh()->getAttributes())->toBe($originalAttributes);
});

test('admins can reset a password and rotate the remember token without changing member attributes', function () {
    $admin = User::factory()->create([
        'phone' => '081111111111',
        'role' => UserRole::Admin,
        'is_active' => true,
    ]);
    $member = User::factory()->create([
        'phone' => '082222222222',
        'is_active' => true,
    ]);
    $originalAttributes = $member->refresh()->getAttributes();

    $response = $this->actingAs($admin)->post(route('roster.reset-password', $member), [
        'password' => 'baru12',
        'password_confirmation' => 'baru12',
        'name' => 'Nama Tidak Diizinkan',
        'phone' => '089999999999',
        'email' => 'tidak-diizinkan@example.com',
        'handicap' => '3+',
        'role' => UserRole::Admin->value,
        'is_active' => false,
        'avatar' => 'avatar-tidak-diizinkan.jpg',
        'remember_token' => 'token-tidak-diizinkan',
    ]);

    $response->assertRedirect(route('roster.show', $member))
        ->assertSessionHas('status_success');
    $member->refresh();
    expect(Hash::check('baru12', $member->password))->toBeTrue();
    expect(Hash::check('password', $member->password))->toBeFalse();
    expect($member->password)->not->toBe($originalAttributes['password']);
    expect($member->remember_token)
        ->not->toBe($originalAttributes['remember_token'])
        ->not->toBe('token-tidak-diizinkan')
        ->not->toBeEmpty();
    expect($member->only(['name', 'phone', 'email', 'avatar']))->toBe([
        'name' => $originalAttributes['name'],
        'phone' => $originalAttributes['phone'],
        'email' => $originalAttributes['email'],
        'avatar' => $originalAttributes['avatar'],
    ]);
    expect($member->handicap->value)->toBe($originalAttributes['handicap']);
    expect($member->role)->toBe(UserRole::Member);
    expect($member->is_active)->toBeTrue();
});

test('invalid reset passwords return a validation message without changing the member', function (array $payload, string $message) {
    $admin = User::factory()->create([
        'phone' => '081111111111',
        'role' => UserRole::Admin,
        'is_active' => true,
    ]);
    $member = User::factory()->create([
        'phone' => '082222222222',
        'is_active' => true,
    ]);
    $originalAttributes = $member->refresh()->getAttributes();

    $response = $this->actingAs($admin)
        ->from(route('roster.show', $member))
        ->post(route('roster.reset-password', $member), $payload);

    $response->assertRedirect(route('roster.show', $member))
        ->assertSessionHasErrors(['password' => $message])
        ->assertSessionMissing('_old_input.password')
        ->assertSessionMissing('_old_input.password_confirmation');
    expect($member->fresh()->getAttributes())->toBe($originalAttributes);
})->with([
    'missing password' => [[], 'Password baru wajib diisi.'],
    'non-text password' => [
        ['password' => ['baru12'], 'password_confirmation' => ['baru12']],
        'Password baru harus berupa teks.',
    ],
    'password shorter than six characters' => [
        ['password' => 'baru1', 'password_confirmation' => 'baru1'],
        'Password baru minimal harus 6 karakter.',
    ],
    'missing confirmation' => [
        ['password' => 'baru12'],
        'Konfirmasi password baru tidak cocok.',
    ],
    'mismatched confirmation' => [
        ['password' => 'baru12', 'password_confirmation' => 'beda12'],
        'Konfirmasi password baru tidak cocok.',
    ],
]);

test('resetting a password deletes only the target member database sessions', function () {
    config(['session.driver' => 'database']);
    $admin = User::factory()->create([
        'phone' => '081111111111',
        'role' => UserRole::Admin,
        'is_active' => true,
    ]);
    $member = User::factory()->create([
        'phone' => '082222222222',
        'is_active' => true,
    ]);
    $otherMember = User::factory()->create([
        'phone' => '083333333333',
        'is_active' => true,
    ]);
    DB::table('sessions')->insert([
        ['id' => 'target-session-one', 'user_id' => $member->id, 'payload' => base64_encode(serialize([])), 'last_activity' => time()],
        ['id' => 'target-session-two', 'user_id' => $member->id, 'payload' => base64_encode(serialize([])), 'last_activity' => time()],
        ['id' => 'admin-existing-session', 'user_id' => $admin->id, 'payload' => base64_encode(serialize([])), 'last_activity' => time()],
        ['id' => 'other-member-session', 'user_id' => $otherMember->id, 'payload' => base64_encode(serialize([])), 'last_activity' => time()],
    ]);

    $response = $this->actingAs($admin)->post(route('roster.reset-password', $member), [
        'password' => 'baru12',
        'password_confirmation' => 'baru12',
    ]);

    $response->assertRedirect(route('roster.show', $member));
    $this->assertDatabaseMissing('sessions', ['user_id' => $member->id]);
    $this->assertDatabaseHas('sessions', ['id' => 'admin-existing-session', 'user_id' => $admin->id]);
    $this->assertDatabaseHas('sessions', ['id' => 'other-member-session', 'user_id' => $otherMember->id]);
});
