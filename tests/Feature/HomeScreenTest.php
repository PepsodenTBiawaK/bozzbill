<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
});

test('guests visiting home page are redirected to login', function () {
    $response = $this->get(route('home'));

    $response->assertRedirect(route('login'));
});

test('authenticated member can view home passport with accurate statistics', function () {
    $aprPinky = User::where('phone', '081111111111')->first();

    $response = $this->actingAs($aprPinky)->get(route('home'));

    $response->assertStatus(200);
    $response->assertSee('Halo, Apr Pinky');
    $response->assertSee('BOZZBILL PLAYER');
    $response->assertSee('HC 3A');
    $response->assertSee('68 %');
    $response->assertSee('25');
    $response->assertSee('4x');
    $response->assertSee('Tren 4 Fun Game Terakhir');
    $response->assertSee('Lencana Prestasi');
    $response->assertSee('Sharpshooter');
});

test('admin sees admin section in navigation', function () {
    $admin = User::where('phone', '081234567890')->first();

    $response = $this->actingAs($admin)->get(route('home'));

    $response->assertStatus(200);
    $response->assertSee('Evaluasi HC');
    $response->assertSee('Pengurus');
    $response->assertSee('href="'.route('dashboard').'"', false);
    $response->assertDontSee('Input Fun Game');
    $response->assertDontSee('Add Member');
});

test('regular member does not see admin-only menu items', function () {
    $member = User::where('phone', '081111111111')->first();

    $response = $this->actingAs($member)->get(route('home'));

    $response->assertStatus(200);
    $response->assertDontSee('Evaluasi HC');
    $response->assertDontSee('href="'.route('dashboard').'"', false);
});
