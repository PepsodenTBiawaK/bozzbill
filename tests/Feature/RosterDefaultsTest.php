<?php

use App\Enums\Handicap;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('roster initially opens the handicap group of the signed in account', function (Handicap $handicap, UserRole $role) {
    $member = User::factory()->create(['handicap' => $handicap, 'role' => $role, 'is_active' => true]);

    $this->actingAs($member)->get(route('roster', ['tab' => 'roster']))
        ->assertOk()
        ->assertViewHas('initialHandicap', $handicap->value)
        ->assertSee("openHc: '{$handicap->value}'", false);
})->with([
    'member 3SB' => [Handicap::ThreeSb, UserRole::Member],
    'member 3B' => [Handicap::ThreeB, UserRole::Member],
    'member 3N' => [Handicap::ThreeN, UserRole::Member],
    'member 3A' => [Handicap::ThreeA, UserRole::Member],
    'member 3+' => [Handicap::ThreePlus, UserRole::Member],
    'admin' => [Handicap::ThreeB, UserRole::Admin],
    'superadmin' => [Handicap::ThreeN, UserRole::Superadmin],
]);

test('roster uses the current handicap after the account handicap changes', function () {
    $member = User::factory()->create(['handicap' => Handicap::ThreeB, 'is_active' => true]);
    $this->actingAs($member)->get(route('roster', ['tab' => 'roster']))
        ->assertOk()->assertViewHas('initialHandicap', '3B');
    $member->update(['handicap' => Handicap::ThreeN]);

    $this->get(route('roster', ['tab' => 'roster']))
        ->assertOk()->assertViewHas('initialHandicap', '3N');
});
