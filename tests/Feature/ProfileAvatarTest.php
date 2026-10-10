<?php

use App\Enums\Handicap;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('guests cannot upload a profile photo', function () {
    Storage::fake('public');

    $response = $this->post(route('profile.update'), [
        '_method' => 'PUT',
        'name' => 'Pemain Baru',
        'avatar' => UploadedFile::fake()->image('profil.png'),
    ]);

    $response->assertRedirect(route('login'));
    expect(Storage::disk('public')->allFiles())->toBeEmpty();
});

test('members and admins can upload supported photos and save their name', function (UserRole $role, string $extension) {
    Storage::fake('public');
    $user = User::factory()->create([
        'phone' => '081111111111',
        'role' => $role,
        'is_active' => true,
    ]);

    $response = $this->actingAs($user)->post(route('profile.update'), [
        '_method' => 'PUT',
        'name' => 'Nama Pemain Baru',
        'avatar' => UploadedFile::fake()->image('nama-asli.'.$extension)->size(2048),
        'remove_avatar' => '0',
    ]);

    $response->assertRedirect(route('profile'))
        ->assertSessionHas('status_success');
    $user->refresh();
    expect($user->name)->toBe('Nama Pemain Baru');
    expect($user->avatar)->toStartWith('avatars/')->not->toContain('nama-asli');
    expect($user->role)->toBe($role);
    Storage::disk('public')->assertExists($user->avatar);
    expect(Storage::disk('public')->allFiles())->toHaveCount(1);
})->with([
    'member' => [UserRole::Member],
    'admin' => [UserRole::Admin],
])->with(['jpg', 'jpeg', 'png', 'webp']);

test('uploading a replacement removes the previous photo after saving the new path', function () {
    Storage::fake('public');
    Storage::disk('public')->put('avatars/previous.jpg', 'old photo');
    $user = User::factory()->create([
        'phone' => '081111111111',
        'avatar' => 'avatars/previous.jpg',
        'is_active' => true,
    ]);

    $response = $this->actingAs($user)->put(route('profile.update'), [
        'name' => $user->name,
        'avatar' => UploadedFile::fake()->image('replacement.png'),
    ]);

    $response->assertRedirect(route('profile'));
    $user->refresh();
    expect($user->avatar)->not->toBe('avatars/previous.jpg');
    Storage::disk('public')->assertExists($user->avatar);
    Storage::disk('public')->assertMissing('avatars/previous.jpg');
    expect(Storage::disk('public')->allFiles())->toHaveCount(1);
});

test('removing a photo clears its path and deletes the old file while saving the name', function () {
    Storage::fake('public');
    Storage::disk('public')->put('avatars/previous.jpg', 'old photo');
    $user = User::factory()->create([
        'phone' => '081111111111',
        'avatar' => 'avatars/previous.jpg',
        'is_active' => true,
    ]);

    $response = $this->actingAs($user)->put(route('profile.update'), [
        'name' => 'Nama Tanpa Foto',
        'remove_avatar' => '1',
    ]);

    $response->assertRedirect(route('profile'))
        ->assertSessionHas('status_success');
    expect($user->fresh()->avatar)->toBeNull();
    expect($user->fresh()->name)->toBe('Nama Tanpa Foto');
    Storage::disk('public')->assertMissing('avatars/previous.jpg');
    expect(Storage::disk('public')->allFiles())->toBeEmpty();
});

test('a name-only update preserves the existing photo', function () {
    Storage::fake('public');
    Storage::disk('public')->put('avatars/previous.jpg', 'old photo');
    $user = User::factory()->create([
        'phone' => '081111111111',
        'avatar' => 'avatars/previous.jpg',
        'is_active' => true,
    ]);

    $response = $this->actingAs($user)->put(route('profile.update'), [
        'name' => 'Nama Diperbarui',
    ]);

    $response->assertRedirect(route('profile'));
    expect($user->fresh()->name)->toBe('Nama Diperbarui');
    expect($user->fresh()->avatar)->toBe('avatars/previous.jpg');
    Storage::disk('public')->assertExists('avatars/previous.jpg');
    expect(Storage::disk('public')->allFiles())->toHaveCount(1);
});

test('profile uploads affect only the signed-in account and ignore protected payload attributes', function () {
    Storage::fake('public');
    Storage::disk('public')->put('avatars/another-member.jpg', 'another photo');
    $user = User::factory()->create([
        'phone' => '081111111111',
        'role' => UserRole::Member,
        'handicap' => Handicap::ThreeN,
        'is_active' => true,
    ]);
    $otherMember = User::factory()->create([
        'phone' => '082222222222',
        'avatar' => 'avatars/another-member.jpg',
        'is_active' => true,
    ]);
    $originalAttributes = $user->refresh()->getAttributes();
    $otherOriginalAttributes = $otherMember->refresh()->getAttributes();

    $response = $this->actingAs($user)->put(route('profile.update'), [
        'name' => 'Nama Baru Milik Sendiri',
        'avatar' => UploadedFile::fake()->image('own-photo.png'),
        'user_id' => $otherMember->id,
        'target' => $otherMember->id,
        'role' => UserRole::Admin->value,
        'handicap' => Handicap::ThreePlus->value,
        'phone' => '089999999999',
        'email' => 'unapproved@example.com',
        'password' => 'unapproved-password',
        'is_active' => false,
    ]);

    $response->assertRedirect(route('profile'));
    $user->refresh();
    expect($user->name)->toBe('Nama Baru Milik Sendiri');
    expect($user->role)->toBe(UserRole::Member);
    expect($user->handicap)->toBe(Handicap::ThreeN);
    expect($user->is_active)->toBeTrue();
    expect($user->only(['phone', 'email', 'password']))->toBe([
        'phone' => $originalAttributes['phone'],
        'email' => $originalAttributes['email'],
        'password' => $originalAttributes['password'],
    ]);
    expect($otherMember->fresh()->getAttributes())->toBe($otherOriginalAttributes);
    Storage::disk('public')->assertExists([$user->avatar, 'avatars/another-member.jpg']);
    expect(Storage::disk('public')->allFiles())->toHaveCount(2);
});

test('invalid photo uploads preserve the old name and photo', function (string $invalidType, string $message) {
    Storage::fake('public');
    Storage::disk('public')->put('avatars/previous.jpg', 'old photo');
    $user = User::factory()->create([
        'phone' => '081111111111',
        'avatar' => 'avatars/previous.jpg',
        'is_active' => true,
    ]);
    $originalAttributes = $user->refresh()->getAttributes();
    $avatar = match ($invalidType) {
        'oversized' => UploadedFile::fake()->image('oversized.png')->size(2049),
        'svg' => UploadedFile::fake()->createWithContent('profil.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'),
        'gif' => UploadedFile::fake()->image('profil.gif'),
        'document' => UploadedFile::fake()->create('profil.pdf', 20, 'application/pdf'),
    };

    $response = $this->actingAs($user)->from(route('profile'))->put(route('profile.update'), [
        'name' => 'Nama Tidak Boleh Disimpan',
        'avatar' => $avatar,
    ]);

    $response->assertRedirect(route('profile'))
        ->assertSessionHasErrors(['avatar' => $message]);
    expect($user->fresh()->getAttributes())->toBe($originalAttributes);
    Storage::disk('public')->assertExists('avatars/previous.jpg');
    expect(Storage::disk('public')->allFiles())->toBe(['avatars/previous.jpg']);
})->with([
    'larger than two megabytes' => ['oversized', 'Ukuran foto profil maksimal 2 MB.'],
    'SVG with active content' => ['svg', 'Foto profil harus berupa gambar.'],
    'unsupported GIF image' => ['gif', 'Foto profil harus berformat JPG, PNG, atau WebP.'],
    'PDF document' => ['document', 'Foto profil harus berupa gambar.'],
]);

test('a non-image file disguised with a PNG filename is rejected', function () {
    Storage::fake('public');
    $user = User::factory()->create([
        'phone' => '081111111111',
        'is_active' => true,
    ]);
    $originalAttributes = $user->refresh()->getAttributes();
    $temporaryFile = tmpfile();
    fwrite($temporaryFile, 'This is a text document, not an image.');
    $avatar = new UploadedFile(stream_get_meta_data($temporaryFile)['uri'], 'profil.png', 'image/png', null, true);

    $response = $this->actingAs($user)->from(route('profile'))->put(route('profile.update'), [
        'name' => 'Nama Tidak Boleh Disimpan',
        'avatar' => $avatar,
    ]);
    fclose($temporaryFile);

    $response->assertRedirect(route('profile'))
        ->assertSessionHasErrors(['avatar' => 'Foto profil harus berupa gambar.']);
    expect($user->fresh()->getAttributes())->toBe($originalAttributes);
    expect(Storage::disk('public')->allFiles())->toBeEmpty();
});

test('a profile update cannot upload and remove a photo at the same time', function () {
    Storage::fake('public');
    Storage::disk('public')->put('avatars/previous.jpg', 'old photo');
    $user = User::factory()->create([
        'phone' => '081111111111',
        'avatar' => 'avatars/previous.jpg',
        'is_active' => true,
    ]);
    $originalAttributes = $user->refresh()->getAttributes();

    $response = $this->actingAs($user)->from(route('profile'))->put(route('profile.update'), [
        'name' => 'Nama Tidak Boleh Disimpan',
        'avatar' => UploadedFile::fake()->image('profil.png'),
        'remove_avatar' => '1',
    ]);

    $response->assertRedirect(route('profile'))
        ->assertSessionHasErrors(['avatar' => 'Pilih foto baru atau hapus foto saat ini, salah satu saja.']);
    expect($user->fresh()->getAttributes())->toBe($originalAttributes);
    expect(Storage::disk('public')->allFiles())->toBe(['avatars/previous.jpg']);
});

test('an invalid name prevents both upload and removal from changing the photo', function (array $payload) {
    Storage::fake('public');
    Storage::disk('public')->put('avatars/previous.jpg', 'old photo');
    $user = User::factory()->create([
        'phone' => '081111111111',
        'avatar' => 'avatars/previous.jpg',
        'is_active' => true,
    ]);
    $originalAttributes = $user->refresh()->getAttributes();
    $response = $this->actingAs($user)->from(route('profile'))->put(route('profile.update'), $payload);

    $response->assertRedirect(route('profile'))
        ->assertSessionHasErrors('name');
    expect($user->fresh()->getAttributes())->toBe($originalAttributes);
    expect(Storage::disk('public')->allFiles())->toBe(['avatars/previous.jpg']);
})->with([
    'upload' => [fn (): array => ['name' => '', 'avatar' => UploadedFile::fake()->image('profil.png')]],
    'remove' => [fn (): array => ['name' => '', 'remove_avatar' => true]],
]);

test('an invalid remove flag cannot change the photo or name', function () {
    Storage::fake('public');
    Storage::disk('public')->put('avatars/previous.jpg', 'old photo');
    $user = User::factory()->create([
        'phone' => '081111111111',
        'avatar' => 'avatars/previous.jpg',
        'is_active' => true,
    ]);
    $originalAttributes = $user->refresh()->getAttributes();

    $response = $this->actingAs($user)->from(route('profile'))->put(route('profile.update'), [
        'name' => 'Nama Tidak Boleh Disimpan',
        'remove_avatar' => 'invalid',
    ]);

    $response->assertRedirect(route('profile'))
        ->assertSessionHasErrors(['remove_avatar' => 'Pilihan hapus foto profil tidak valid.']);
    expect($user->fresh()->getAttributes())->toBe($originalAttributes);
    expect(Storage::disk('public')->allFiles())->toBe(['avatars/previous.jpg']);
});

test('a failed database save removes the new upload and keeps the previous photo', function () {
    Storage::fake('public');
    Storage::disk('public')->put('avatars/previous.jpg', 'old photo');
    $user = User::factory()->create([
        'phone' => '081111111111',
        'avatar' => 'avatars/previous.jpg',
        'is_active' => true,
    ]);
    $originalAttributes = $user->refresh()->getAttributes();
    Exceptions::fake([RuntimeException::class]);
    Event::listen('eloquent.updating: '.User::class, function (User $updatingUser) use ($user): void {
        if ($updatingUser->is($user)) {
            throw new RuntimeException('Simulated database write failure.');
        }
    });

    $response = $this->actingAs($user)->put(route('profile.update'), [
        'name' => 'Nama Tidak Boleh Disimpan',
        'avatar' => UploadedFile::fake()->image('replacement.png'),
    ]);

    $response->assertInternalServerError();
    Exceptions::assertReported(RuntimeException::class);
    expect($user->fresh()->getAttributes())->toBe($originalAttributes);
    Storage::disk('public')->assertExists('avatars/previous.jpg');
    expect(Storage::disk('public')->allFiles())->toBe(['avatars/previous.jpg']);
});

test('profile photos use the shared avatar display on the home profile and member detail pages', function (string $routeName) {
    Storage::fake('public');
    Storage::disk('public')->put('avatars/display.png', 'photo');
    $user = User::factory()->create([
        'name' => 'Pemain Lokal',
        'phone' => '081111111111',
        'role' => UserRole::Member,
        'handicap' => Handicap::ThreeN,
        'avatar' => 'avatars/display.png',
        'is_active' => true,
    ]);

    $response = $this->actingAs($user)->get(route($routeName, $routeName === 'roster.show' ? $user : []));

    $response->assertOk()
        ->assertSee('src="'.Storage::disk('public')->url('avatars/display.png').'"', false)
        ->assertSee('alt="Foto profil Pemain Lokal"', false);
    Storage::disk('public')->assertExists('avatars/display.png');
})->with(['home', 'profile', 'roster.show']);

test('the own profile offers photo settings initials fallback and drilling access', function () {
    Storage::fake('public');
    $user = User::factory()->create([
        'name' => 'Pemain Lokal',
        'phone' => '081111111111',
        'role' => UserRole::Member,
        'handicap' => Handicap::ThreeN,
        'is_active' => true,
    ]);

    $response = $this->actingAs($user)->get(route('profile'));

    $response->assertOk()
        ->assertSee('PL')
        ->assertSee('enctype="multipart/form-data"', false)
        ->assertSee('name="avatar"', false)
        ->assertSee('href="'.route('drilling').'"', false)
        ->assertDontSee('alt="Foto profil Pemain Lokal"', false);
    expect(Storage::disk('public')->allFiles())->toBeEmpty();
});
