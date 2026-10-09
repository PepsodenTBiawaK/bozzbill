<?php

use App\Models\PlayerDrill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
});

test('guests visiting drilling page are redirected to login', function () {
    $response = $this->get(route('drilling'));

    $response->assertRedirect(route('login'));
});

test('authenticated member can view drilling page with stats and journal history', function () {
    $aprPinky = User::where('phone', '081111111111')->first();

    $response = $this->actingAs($aprPinky)->get(route('drilling'));

    $response->assertStatus(200);
    $response->assertSee('Jurnal Drilling');
    $response->assertSee('Catat Sesi Latihan');
    $response->assertSee('Riwayat Drilling');
    $response->assertSee('Straight Shot 10x Corner Pocket');
    $response->assertSee('Total Drilling');
    $response->assertSee('Drilling Bulan Ini');
});

test('member can record a new drill session successfully', function () {
    $aprPinky = User::where('phone', '081111111111')->first();

    $response = $this->actingAs($aprPinky)->post(route('drilling.store'), [
        'drill_name' => 'Side Pocket Bank Shot',
        'success_count' => 8,
        'fail_count' => 2,
        'practice_date' => now()->toDateString(),
        'notes' => 'Fokus kecepatan cue dan sudut datang bola sasaran.',
    ]);

    $response->assertRedirect(route('drilling'));
    $response->assertSessionHas('status_success');

    $drill = PlayerDrill::where('user_id', $aprPinky->id)
        ->where('drill_name', 'Side Pocket Bank Shot')
        ->first();

    expect($drill)->not->toBeNull()
        ->and($drill->total_attempts)->toBe(10)
        ->and($drill->accuracy_percentage)->toBe(80.0)
        ->and($drill->notes)->toBe('Fokus kecepatan cue dan sudut datang bola sasaran.');
});

test('recording drill with zero attempts fails validation', function () {
    $aprPinky = User::where('phone', '081111111111')->first();

    $response = $this->actingAs($aprPinky)->post(route('drilling.store'), [
        'drill_name' => 'Empty Drill',
        'success_count' => 0,
        'fail_count' => 0,
    ]);

    $response->assertSessionHasErrors('success_count');
});

test('member can record drill when practice_date is omitted from payload', function () {
    $aprPinky = User::where('phone', '081111111111')->first();

    $response = $this->actingAs($aprPinky)->post(route('drilling.store'), [
        'drill_name' => 'Drill Without Date Key',
        'success_count' => 9,
        'fail_count' => 1,
    ]);

    $response->assertRedirect(route('drilling'));
    $response->assertSessionHas('status_success');

    $drill = PlayerDrill::where('user_id', $aprPinky->id)
        ->where('drill_name', 'Drill Without Date Key')
        ->first();

    expect($drill)->not->toBeNull()
        ->and($drill->practice_date->toDateString())->toBe(now()->toDateString());
});
