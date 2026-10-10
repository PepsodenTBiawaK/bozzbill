<?php

use App\Enums\Handicap;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('the first admin is active and can log in after entering a hidden password', function () {
    $this->artisan('bozzbill:create-admin')
        ->expectsQuestion('Nama pengurus', '  Pengurus Bozz  ')
        ->expectsQuestion('Nomor WhatsApp', '+62 812-3456-7890')
        ->expectsQuestion('Password admin (minimal 8 karakter)', 'admin-secret-123')
        ->expectsQuestion('Ulangi password admin', 'admin-secret-123')
        ->doesntExpectOutputToContain('admin-secret-123')
        ->assertSuccessful();

    $this->assertDatabaseHas('users', [
        'name' => 'Pengurus Bozz',
        'phone' => '081234567890',
        'role' => UserRole::Superadmin->value,
        'handicap' => Handicap::ThreeN->value,
        'is_active' => true,
        'email' => null,
    ]);
    $admin = User::where('phone', '081234567890')->firstOrFail();
    expect(Hash::check('admin-secret-123', $admin->password))->toBeTrue();

    $this->post(route('login.post'), [
        'phone' => '+6281234567890',
        'password' => 'admin-secret-123',
    ])->assertRedirect(route('home'))->assertSessionHasNoErrors();
    $this->assertAuthenticatedAs($admin);
});

test('name and phone options still require a hidden password', function () {
    $this->artisan('bozzbill:create-admin', [
        '--name' => 'Pengurus Bozz',
        '--phone' => '081234567890',
    ])
        ->expectsQuestion('Password admin (minimal 8 karakter)', 'admin-secret-123')
        ->expectsQuestion('Ulangi password admin', 'admin-secret-123')
        ->assertSuccessful();

    $this->assertDatabaseHas('users', ['phone' => '081234567890', 'role' => UserRole::Superadmin->value]);
});

test('admin initialization refuses noninteractive execution', function () {
    $this->artisan('bozzbill:create-admin', [
        '--name' => 'Pengurus Bozz',
        '--phone' => '081234567890',
        '--no-interaction' => true,
    ])
        ->expectsOutput('Jalankan perintah ini secara interaktif agar password dapat dimasukkan dengan aman.')
        ->assertFailed();

    $this->assertDatabaseEmpty('users');
});

test('admin initialization leaves an existing admin unchanged', function (bool $isActive) {
    $admin = User::factory()->create([
        'phone' => '081111111111',
        'role' => UserRole::Admin,
        'is_active' => $isActive,
    ]);
    $attributes = $admin->refresh()->getAttributes();

    $this->artisan('bozzbill:create-admin', [
        '--name' => 'Pengurus Baru',
        '--phone' => '082222222222',
    ])
        ->expectsOutput('Akun pengurus sudah ada. Perintah ini hanya untuk pengurus pertama.')
        ->assertFailed();

    $this->assertDatabaseCount('users', 1);
    expect($admin->fresh()->getAttributes())->toBe($attributes);
})->with(['active admin' => true, 'inactive admin' => false]);

test('admin initialization never promotes or overwrites an existing member', function () {
    $member = User::factory()->create([
        'phone' => '081234567890',
        'role' => UserRole::Member,
        'is_active' => false,
    ]);
    $attributes = $member->refresh()->getAttributes();

    $this->artisan('bozzbill:create-admin', [
        '--name' => 'Pengurus Bozz',
        '--phone' => '+6281234567890',
    ])
        ->expectsOutput('Nomor WhatsApp ini sudah terdaftar. Akun yang ada tidak akan diubah.')
        ->assertFailed();

    $this->assertDatabaseCount('users', 1);
    expect($member->fresh()->getAttributes())->toBe($attributes);
});

test('admin initialization rejects invalid identity details without creating a user', function (string $name, string $phone, string $message) {
    $this->artisan('bozzbill:create-admin', ['--name' => $name, '--phone' => $phone])
        ->expectsOutput($message)
        ->assertFailed();

    $this->assertDatabaseEmpty('users');
})->with([
    'blank name' => [' ', '081234567890', 'Nama pengurus wajib diisi.'],
    'long name' => [str_repeat('a', 256), '081234567890', 'Nama pengurus maksimal 255 karakter.'],
    'blank phone' => ['Pengurus', '', 'Nomor WhatsApp wajib diisi.'],
    'invalid phone' => ['Pengurus', '1234', 'Gunakan nomor WhatsApp Indonesia yang valid, misalnya 081234567890.'],
]);

test('admin initialization rejects invalid passwords without creating a user', function (string $password, string $confirmation, string $message) {
    $this->artisan('bozzbill:create-admin', ['--name' => 'Pengurus', '--phone' => '081234567890'])
        ->expectsQuestion('Password admin (minimal 8 karakter)', $password)
        ->expectsQuestion('Ulangi password admin', $confirmation)
        ->expectsOutput($message)
        ->assertFailed();

    $this->assertDatabaseEmpty('users');
})->with([
    'blank password' => ['', '', 'Password admin wajib diisi.'],
    'short password' => ['short', 'short', 'Password admin minimal 8 karakter.'],
    'mismatched confirmation' => ['admin-secret-123', 'other-secret-123', 'Konfirmasi password tidak cocok.'],
]);
