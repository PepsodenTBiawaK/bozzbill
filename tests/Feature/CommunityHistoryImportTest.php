<?php

use App\Enums\Handicap;
use App\Enums\UserRole;
use App\Models\FunGame;
use App\Models\HcHistory;
use App\Models\MatchLog;
use App\Models\User;
use Database\Seeders\ProductionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->archivePaths = [];
});

afterEach(function () {
    foreach ($this->archivePaths ?? [] as $path) {
        unlink($path);
    }
});

function communityHistoryFixture(): array
{
    $events = [];
    foreach ([17, 18, 19, 20] as $number) {
        $opponent = in_array($number, [17, 20], true) ? 'Arya' : 'Satrio';
        $winner = $number >= 19 ? 'Fadil' : $opponent;
        $events[] = [
            'code' => 'FG'.$number, 'title' => 'Fun Game #'.$number,
            'event_date' => [17 => '2026-09-06', 18 => '2026-09-13', 19 => '2026-09-27', 20 => '2026-10-04'][$number],
            'format' => 'single', 'status' => 'completed', 'notes' => 'Rekap sebagian.',
            'podium' => [['position' => 1, 'names' => [$winner]], ['position' => 2, 'names' => [$winner === 'Fadil' ? $opponent : 'Fadil']]],
            'matches' => [['key' => 'row1', 'player1' => 'Fadil', 'player2' => $opponent, 'winner' => $winner,
                'player1_hc' => '3A', 'player2_hc' => '3B']],
        ];
    }
    $events[] = ['code' => 'DBL1', 'title' => 'Double #1', 'event_date' => '2026-09-20', 'format' => 'double', 'status' => 'completed',
        'podium' => [['position' => 1, 'names' => ['Fadil', 'Partner']], ['position' => 2, 'names' => ['Arya', 'Satrio']]], 'matches' => []];
    $events[] = ['code' => 'FG03', 'title' => 'FG03 Libur', 'event_date' => '2026-05-31', 'format' => 'single', 'status' => 'cancelled', 'podium' => [], 'matches' => []];

    return ['source' => 'bozz-test', 'members' => [
        ['name' => 'Fadil', 'phone' => '081100000001', 'handicap' => '3A'],
        ['name' => 'Arya', 'phone' => null, 'handicap' => '3B'],
        ['name' => 'Satrio', 'phone' => '081100000003', 'handicap' => '3N'],
        ['name' => 'Partner', 'phone' => null, 'handicap' => '3A'],
    ], 'events' => $events, 'hc_changes' => [
        ['key' => 'satrio-fg20', 'name' => 'Satrio', 'old_hc' => '3B', 'new_hc' => '3N', 'effective_date' => '2026-10-04', 'reason' => 'Mulai FG20, dikonfirmasi pengurus.'],
    ]];
}

function writeCommunityHistoryFixture(array $data): string
{
    $path = tempnam(sys_get_temp_dir(), 'bozz-history-');
    file_put_contents($path, json_encode($data, JSON_THROW_ON_ERROR));
    test()->archivePaths = [...(test()->archivePaths ?? []), $path];

    return $path;
}

function createCommunityHistoryOwner(): User
{
    test()->seed(ProductionSeeder::class);

    return User::factory()->create(['name' => 'Fadil', 'phone' => '081100000001', 'role' => UserRole::Superadmin, 'is_active' => true]);
}

test('history preview rolls back every imported record including changes to Fadil', function () {
    $owner = createCommunityHistoryOwner();
    $original = $owner->refresh()->getAttributes();
    $path = writeCommunityHistoryFixture(communityHistoryFixture());
    $this->artisan('bozzbill:import-history', ['file' => $path, '--fadil' => $owner->id])->assertSuccessful();
    $this->assertDatabaseCount('users', 1);
    $this->assertDatabaseEmpty('fun_games');
    $this->assertDatabaseEmpty('match_logs');
    $this->assertDatabaseEmpty('hc_histories');
    $this->assertDatabaseEmpty('badge_user');
    expect($owner->fresh()->getAttributes())->toBe($original);
});

test('history import preserves Fadil and saves distinct fixtures podium teams and unknown scores', function () {
    $owner = createCommunityHistoryOwner();
    $original = $owner->only(['id', 'name', 'phone', 'password', 'role', 'is_active']);
    $path = writeCommunityHistoryFixture(communityHistoryFixture());
    $this->artisan('bozzbill:import-history', ['file' => $path, '--fadil' => $owner->id, '--apply' => true])->assertSuccessful();
    $this->assertDatabaseCount('users', 4);
    $this->assertDatabaseCount('fun_games', 6);
    $this->assertDatabaseCount('match_logs', 4);
    $this->assertDatabaseCount('hc_histories', 5);
    expect($owner->fresh()->only(array_keys($original)))->toBe($original);
    expect($owner->fresh()->handicap)->toBe(Handicap::ThreeA);
    $arya = User::where('name', 'Arya')->firstOrFail();
    expect($arya->phone)->toBeNull();
    expect($arya->password)->toBeNull();
    expect($arya->is_active)->toBeFalse();
    expect($arya->badges()->pluck('slug')->all())->toContain('champion', 'giant-killer');
    expect($arya->funGamesParticipatedCount())->toBe(3);
    $double = FunGame::where('format', 'double')->firstOrFail();
    expect($double->champion_id)->toBeNull();
    expect($double->podiumPlayers()->count())->toBe(4);
    expect($double->podiumName(1))->toBe('Fadil & Partner');
    expect($double->podiumName(2))->toBe('Arya & Satrio');
    expect($owner->fresh()->evaluateHandicap()['total_matches'])->toBe(0);
    expect($owner->fresh()->evaluateHandicap()['wins'])->toBe(0);
    expect($owner->fresh()->evaluateHandicap()['excluded_matches'])->toBe(4);
    expect($owner->fresh()->recentFunGamesSummary())->toHaveCount(4);
    expect($arya->recentFunGamesSummary()[1]['label'])->toBe('Belum tercatat');
    expect(HcHistory::whereNotNull('old_hc')->firstOrFail()->old_hc)->toBe(Handicap::ThreeB);
    $match = MatchLog::firstOrFail();
    expect($match->player1_score)->toBeNull();
    expect($match->player2_score)->toBeNull();
    expect($match->scoreSummary())->toBe('Skor Tidak Tercatat');

    $this->actingAs($owner)->get(route('home'))->assertOk()->assertSee('Skor Tidak Tercatat');
    $this->get(route('dashboard'))->assertOk()->assertSee('Skor Tidak Tercatat');
    $this->get(route('roster.show', $arya))->assertOk()->assertSee('Skor Tidak Tercatat')->assertSee('Nomor WhatsApp belum tersedia');
    $this->get(route('head-to-head', ['player1_id' => $owner->id, 'opponent_id' => $arya->id]))->assertOk()->assertSee('Skor Tidak Tercatat');
    $this->get(route('fun-games.show', $match->funGame))->assertOk()->assertSee('Skor Tidak Tercatat')->assertSee('Rekap histori belum lengkap');
    $this->get(route('fun-games.show', $double))->assertOk()->assertSee('Fadil & Partner')->assertSee('Arya & Satrio');
    $this->get(route('fun-games.index'))->assertOk()->assertSee('Fadil & Partner')->assertSee('Libur');
    $cancelled = FunGame::where('status', 'cancelled')->firstOrFail();
    $this->get(route('fun-games.show', $cancelled))->assertOk()->assertSee('Event libur')->assertDontSee('Tutup & Nobatkan');
    $this->postJson(route('fun-games.close', $cancelled), ['champion_id' => $owner->id])->assertUnprocessable();
});

test('repeating import does not duplicate data or revert later account edits', function () {
    $owner = createCommunityHistoryOwner();
    $path = writeCommunityHistoryFixture(communityHistoryFixture());
    $args = ['file' => $path, '--fadil' => $owner->id, '--apply' => true];
    $this->artisan('bozzbill:import-history', $args)->assertSuccessful();
    $player = User::where('name', 'Satrio')->firstOrFail();
    $player->update(['phone' => '081100000009', 'handicap' => '3A', 'role' => UserRole::Admin]);
    $this->artisan('bozzbill:import-history', $args)->assertSuccessful();
    $this->assertDatabaseCount('users', 4);
    $this->assertDatabaseCount('fun_games', 6);
    $this->assertDatabaseCount('match_logs', 4);
    $this->assertDatabaseCount('hc_histories', 5);
    expect($player->fresh()->phone)->toBe('081100000009');
    expect($player->fresh()->handicap)->toBe(Handicap::ThreeA);
    expect($player->fresh()->role)->toBe(UserRole::Admin);
});

test('archive profiles can receive a real phone and activate without losing their history', function () {
    $owner = createCommunityHistoryOwner();
    $path = writeCommunityHistoryFixture(communityHistoryFixture());
    $this->artisan('bozzbill:import-history', ['file' => $path, '--fadil' => $owner->id, '--apply' => true])->assertSuccessful();
    $arya = User::where('name', 'Arya')->firstOrFail();
    $this->actingAs($owner)->put(route('roster.phone', $arya), ['phone' => '+6281100000002'])->assertRedirect();
    $this->post(route('logout'));
    $this->post(route('activate.post'), ['activation_code' => issueTestActivationCode($arya), 'phone' => '081100000002', 'password' => 'member123', 'password_confirmation' => 'member123'])->assertSessionHasNoErrors();
    expect($arya->fresh()->is_active)->toBeTrue();
    expect($arya->fresh()->totalMatchesCount())->toBe(2);
    expect($arya->fresh()->totalWinsCount())->toBe(1);
    expect($arya->fresh()->badges()->count())->toBe(2);
    $this->assertDatabaseCount('users', 4);
});

test('an ambiguous phone collision rolls back the whole import', function () {
    $owner = createCommunityHistoryOwner();
    User::factory()->create(['name' => 'Akun Berbeda', 'phone' => '081100000003']);
    $path = writeCommunityHistoryFixture(communityHistoryFixture());
    $this->artisan('bozzbill:import-history', ['file' => $path, '--fadil' => $owner->id, '--apply' => true])->assertFailed();
    $this->assertDatabaseCount('users', 2);
    $this->assertDatabaseEmpty('fun_games');
    $this->assertDatabaseEmpty('match_logs');
    $this->assertDatabaseEmpty('hc_histories');
    expect($owner->fresh()->handicap)->toBe(Handicap::ThreeN);
});

test('invalid podium or match references prevent any import', function (string $kind) {
    $owner = createCommunityHistoryOwner();
    $data = communityHistoryFixture();
    if ($kind === 'winner') {
        $data['events'][0]['matches'][0]['winner'] = 'Satrio';
    } else {
        $data['events'][0]['podium'][0]['names'] = ['Unknown'];
    }
    $path = writeCommunityHistoryFixture($data);
    $this->artisan('bozzbill:import-history', ['file' => $path, '--fadil' => $owner->id, '--apply' => true])->assertFailed();
    $this->assertDatabaseCount('users', 1);
    $this->assertDatabaseEmpty('fun_games');
})->with(['winner', 'podium']);
