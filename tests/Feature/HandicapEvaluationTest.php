<?php

use App\Enums\Handicap;
use App\Models\HcHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
});

test('guests visiting evaluating hc page are redirected to login', function () {
    $response = $this->get(route('evaluating-hc.index'));

    $response->assertRedirect(route('login'));
});

test('regular members cannot access evaluating hc page', function () {
    $member = User::where('phone', '081111111111')->first();

    $response = $this->actingAs($member)->get(route('evaluating-hc.index'));

    $response->assertStatus(403);
});

test('admin can access evaluating hc page and view evaluation cards', function () {
    $admin = User::where('phone', '081234567890')->first();

    $response = $this->actingAs($admin)->get(route('evaluating-hc.index'));

    $response->assertStatus(200);
    $response->assertSee('Evaluasi Handicap');
    $response->assertSee('Mode Pengurus BozzBill');
    $response->assertSee('Tinjau Pemain');
    $response->assertSee('Current HC:');
});

test('admin can filter evaluations by recommendation status', function () {
    $admin = User::where('phone', '081234567890')->first();

    $response = $this->actingAs($admin)->get(route('evaluating-hc.index', ['filter' => 'watch']));

    $response->assertStatus(200);
    $response->assertSee('Pantau');
});

test('admin can approve handicap promotion and update player handicap', function () {
    $admin = User::where('phone', '081234567890')->first();
    $budi = User::where('phone', '085555555555')->first(); // Currently HC 3B

    expect($budi->handicap)->toBe(Handicap::ThreeB);

    $response = $this->actingAs($admin)->post(route('evaluating-hc.approve', $budi), [
        'decision' => 'promote',
        'new_hc' => Handicap::ThreeN->value,
        'reason' => 'Performa sangat konsisten di Fun Game 4 event terakhir.',
    ]);

    $response->assertRedirect(route('evaluating-hc.index'));
    $response->assertSessionHas('status_success');

    $budi->refresh();
    expect($budi->handicap)->toBe(Handicap::ThreeN);

    $history = HcHistory::where('user_id', $budi->id)->latest('id')->first();
    expect($history)->not->toBeNull()
        ->and($history->old_hc)->toBe(Handicap::ThreeB)
        ->and($history->new_hc)->toBe(Handicap::ThreeN)
        ->and($history->changed_by)->toBe($admin->id);
});
