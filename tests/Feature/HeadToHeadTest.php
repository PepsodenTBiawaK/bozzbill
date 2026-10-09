<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
});

test('guests visiting head to head page are redirected to login', function () {
    $response = $this->get(route('head-to-head'));

    $response->assertRedirect(route('login'));
});

test('authenticated member can view head to head page with accurate duel stats', function () {
    $aprPinky = User::where('phone', '081111111111')->first();
    $pedong = User::where('phone', '082222222222')->first();

    $response = $this->actingAs($aprPinky)->get(route('head-to-head'));

    $response->assertStatus(200);
    $response->assertSee('Head to Head');
    $response->assertSee('Apr Pinky');
    $response->assertSee('Pedong');
    $response->assertSee('VS');
    $response->assertSee('5 Match');
    $response->assertSee('Riwayat Pertemuan');
    $response->assertSee('Babak Penyisihan');
});

test('member can switch opponent via query parameter', function () {
    $aprPinky = User::where('phone', '081111111111')->first();
    $nina = User::where('phone', '084444444444')->first();

    $response = $this->actingAs($aprPinky)->get(route('head-to-head', ['opponent_id' => $nina->id]));

    $response->assertStatus(200);
    $response->assertSee('Apr Pinky');
    $response->assertSee('Nina');
});
