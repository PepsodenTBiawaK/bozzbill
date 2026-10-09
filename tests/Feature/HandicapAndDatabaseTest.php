<?php

use App\Enums\Handicap;
use App\Models\PlayerDrill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
});

test('handicap hierarchy rank and comparison behaves according to rules', function () {
    expect(Handicap::ThreeSb->rank())->toBe(1)
        ->and(Handicap::ThreeB->rank())->toBe(2)
        ->and(Handicap::ThreeN->rank())->toBe(3)
        ->and(Handicap::ThreeA->rank())->toBe(4)
        ->and(Handicap::ThreePlus->rank())->toBe(5);

    expect(Handicap::ThreeA->isHigherThan(Handicap::ThreeN))->toBeTrue()
        ->and(Handicap::ThreeSb->isLowerThan(Handicap::ThreeB))->toBeTrue()
        ->and(Handicap::ThreePlus->next())->toBeNull()
        ->and(Handicap::ThreeSb->previous())->toBeNull()
        ->and(Handicap::ThreeN->next())->toBe(Handicap::ThreeA);
});

test('seeded users and match relationships calculate correct stats', function () {
    $aprPinky = User::where('phone', '081111111111')->first();
    expect($aprPinky)->not->toBeNull();
    expect($aprPinky->handicap)->toBe(Handicap::ThreeA);
    expect($aprPinky->totalMatchesCount())->toBe(25);
    expect($aprPinky->winRatePercentage())->toBe(68.0);
    expect($aprPinky->badges()->count())->toBe(3);
    expect($aprPinky->playerDrills()->count())->toBe(3);
});

test('player drill automatically calculates accuracy on save', function () {
    $user = User::where('phone', '081111111111')->first();

    $drill = PlayerDrill::create([
        'user_id' => $user->id,
        'drill_name' => 'Bank Shot Corner',
        'success_count' => 8,
        'fail_count' => 2,
        'practice_date' => now()->toDateString(),
    ]);

    expect($drill->total_attempts)->toBe(10)
        ->and($drill->accuracy_percentage)->toBe(80.0);
});

test('unactivated user exists with null password', function () {
    $unactivated = User::where('phone', '087777777777')->first();
    expect($unactivated)->not->toBeNull()
        ->and($unactivated->is_active)->toBeFalse()
        ->and($unactivated->password)->toBeNull();
});
