<?php

use App\Enums\Handicap;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
});

test('guests visiting roster page are redirected to login', function () {
    $response = $this->get(route('roster'));

    $response->assertRedirect(route('login'));
});

test('authenticated member can view leaderboard with podium and rankings', function () {
    $member = User::where('phone', '081111111111')->first();

    $response = $this->actingAs($member)->get(route('roster'));

    $response->assertStatus(200);
    $response->assertSee('Komunitas BozzBill');
    $response->assertSee('Leaderboard');
    $response->assertSee('Roster Anggota');
    $response->assertSee('Win Rate Tertinggi');
    $response->assertSee('Peringkat Pemain');
});

test('member can view roster tab with handicap tiers', function () {
    $member = User::where('phone', '081111111111')->first();

    $response = $this->actingAs($member)->get(route('roster', ['tab' => 'roster']));

    $response->assertStatus(200);
    $response->assertSee('Handicap 3A');
    $response->assertSee('Handicap 3N');
    $response->assertSee('Handicap 3B');
    $response->assertSee('Handicap 3SB');
});

test('member can search roster by player name', function () {
    $member = User::where('phone', '081111111111')->first();

    $response = $this->actingAs($member)->get(route('roster', ['tab' => 'roster', 'search' => 'Pedong']));

    $response->assertStatus(200);
    $response->assertSee('Pedong');
});

test('regular member cannot register new member', function () {
    $member = User::where('phone', '081111111111')->first();

    $response = $this->actingAs($member)->post(route('members.store'), [
        'name' => 'Member Baru',
        'phone' => '089988776655',
        'handicap' => '3N',
    ]);

    $response->assertStatus(403);
});

test('admin can register a new unactivated member', function () {
    $admin = User::where('phone', '081234567890')->first();

    $response = $this->actingAs($admin)->post(route('members.store'), [
        'name' => 'Bintang Biliar',
        'phone' => '089988776655',
        'handicap' => '3B',
    ]);

    $response->assertRedirect(route('roster', ['tab' => 'roster']));
    $response->assertSessionHas('status_success');

    $newMember = User::where('phone', '089988776655')->first();
    expect($newMember)->not->toBeNull()
        ->and($newMember->name)->toBe('Bintang Biliar')
        ->and($newMember->handicap)->toBe(Handicap::ThreeB)
        ->and($newMember->is_active)->toBeFalse()
        ->and($newMember->password)->toBeNull();
});

test('registering member with existing phone returns validation error', function () {
    $admin = User::where('phone', '081234567890')->first();

    $response = $this->actingAs($admin)->post(route('members.store'), [
        'name' => 'Duplikat Member',
        'phone' => '081111111111', // Apr Pinky's phone
        'handicap' => '3N',
    ]);

    $response->assertSessionHasErrors('phone');
});
