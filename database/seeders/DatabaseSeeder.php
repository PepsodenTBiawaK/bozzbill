<?php

namespace Database\Seeders;

use App\Enums\FunGameStatus;
use App\Enums\Handicap;
use App\Enums\UserRole;
use App\Models\Badge;
use App\Models\FunGame;
use App\Models\HcHistory;
use App\Models\MatchLog;
use App\Models\PlayerDrill;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Seed Achievement Badges
        $badgeSharpshooter = Badge::create([
            'name' => 'Sharpshooter',
            'slug' => 'sharpshooter',
            'description' => 'Akurasi drilling latihan mandiri mencapai di atas 80%',
            'icon' => 'target',
        ]);

        $badgeElectric = Badge::create([
            'name' => 'Electric Form',
            'slug' => 'electric-form',
            'description' => 'Aktif bertanding di 4 event Fun Game berturut-turut',
            'icon' => 'zap',
        ]);

        $badgeChampion = Badge::create([
            'name' => 'Champion',
            'slug' => 'champion',
            'description' => 'Meraih Juara 1 pada event Fun Game resmi Komunitas Bozz',
            'icon' => 'trophy',
        ]);

        $badgeGiantKiller = Badge::create([
            'name' => 'Giant Killer',
            'slug' => 'giant-killer',
            'description' => 'Menang dalam pertandingan melawan pemain dengan HC lebih tinggi',
            'icon' => 'swords',
        ]);

        $badgeDrillMaster = Badge::create([
            'name' => 'Drill Master',
            'slug' => 'drill-master',
            'description' => 'Mencatat lebih dari 50x percobaan latihan drilling mandiri',
            'icon' => 'flame',
        ]);

        // 2. Seed Admin User
        $admin = User::create([
            'name' => 'Admin Bozz Billiard',
            'phone' => '081234567890',
            'email' => 'admin@bozzbill.test',
            'password' => Hash::make('password'),
            'handicap' => Handicap::ThreePlus,
            'role' => UserRole::Admin,
            'is_active' => true,
        ]);

        // 3. Seed Core Community Members
        $aprPinky = User::create([
            'name' => 'Apr Pinky',
            'phone' => '081111111111',
            'email' => 'apr.pinky@bozzbill.test',
            'password' => Hash::make('password'),
            'handicap' => Handicap::ThreeA,
            'role' => UserRole::Member,
            'is_active' => true,
        ]);

        $pedong = User::create([
            'name' => 'Pedong',
            'phone' => '082222222222',
            'email' => 'pedong@bozzbill.test',
            'password' => Hash::make('password'),
            'handicap' => Handicap::ThreeA,
            'role' => UserRole::Member,
            'is_active' => true,
        ]);

        $fadil = User::create([
            'name' => 'Fadil',
            'phone' => '083333333333',
            'email' => 'fadil@bozzbill.test',
            'password' => Hash::make('password'),
            'handicap' => Handicap::ThreeN,
            'role' => UserRole::Member,
            'is_active' => true,
        ]);

        $nina = User::create([
            'name' => 'Nina',
            'phone' => '084444444444',
            'email' => 'nina@bozzbill.test',
            'password' => Hash::make('password'),
            'handicap' => Handicap::ThreeN,
            'role' => UserRole::Member,
            'is_active' => true,
        ]);

        $budi = User::create([
            'name' => 'Budi Santoso',
            'phone' => '085555555555',
            'email' => 'budi@bozzbill.test',
            'password' => Hash::make('password'),
            'handicap' => Handicap::ThreeB,
            'role' => UserRole::Member,
            'is_active' => true,
        ]);

        $rian = User::create([
            'name' => 'Rian Bill',
            'phone' => '086666666666',
            'email' => 'rian@bozzbill.test',
            'password' => Hash::make('password'),
            'handicap' => Handicap::ThreeSb,
            'role' => UserRole::Member,
            'is_active' => true,
        ]);

        // Unactivated Member for Testing Self-Activation Flow
        $unactivated = User::create([
            'name' => 'Calon Member Bozz',
            'phone' => '087777777777',
            'email' => null,
            'password' => null,
            'handicap' => Handicap::ThreeN,
            'role' => UserRole::Member,
            'is_active' => false,
        ]);

        // Attach Badges
        $aprPinky->badges()->attach([
            $badgeSharpshooter->id => ['awarded_at' => Carbon::now()->subDays(10)],
            $badgeElectric->id => ['awarded_at' => Carbon::now()->subDays(5)],
            $badgeChampion->id => ['awarded_at' => Carbon::now()->subDays(2)],
        ]);

        $pedong->badges()->attach([
            $badgeElectric->id => ['awarded_at' => Carbon::now()->subDays(5)],
            $badgeGiantKiller->id => ['awarded_at' => Carbon::now()->subDays(12)],
        ]);

        $fadil->badges()->attach([
            $badgeSharpshooter->id => ['awarded_at' => Carbon::now()->subDays(8)],
            $badgeDrillMaster->id => ['awarded_at' => Carbon::now()->subDays(4)],
        ]);

        // 4. Seed 4 Recent Fun Game Events (Completed) + 1 Active (Open)
        $fg1 = FunGame::create([
            'title' => 'Fun Game Bozz #9 - Weekend Kickoff',
            'event_date' => Carbon::now()->subWeeks(4)->toDateString(),
            'status' => FunGameStatus::Completed,
            'champion_id' => $pedong->id,
            'runner_up_id' => $aprPinky->id,
            'third_place_id' => $fadil->id,
            'notes' => 'Event pembuka awal bulan di Meja Rasson Tebet.',
            'created_by' => $admin->id,
        ]);

        $fg2 = FunGame::create([
            'title' => 'Fun Game Bozz #10 - Night Session',
            'event_date' => Carbon::now()->subWeeks(3)->toDateString(),
            'status' => FunGameStatus::Completed,
            'champion_id' => $nina->id,
            'runner_up_id' => $pedong->id,
            'third_place_id' => $aprPinky->id,
            'notes' => 'Sesi malam seru dengan 16 peserta.',
            'created_by' => $admin->id,
        ]);

        $fg3 = FunGame::create([
            'title' => 'Fun Game Bozz #11 - Rasson Challenge',
            'event_date' => Carbon::now()->subWeeks(2)->toDateString(),
            'status' => FunGameStatus::Completed,
            'champion_id' => $aprPinky->id,
            'runner_up_id' => $fadil->id,
            'third_place_id' => $budi->id,
            'notes' => 'Apr Pinky tampil dominan sepanjang turnamen.',
            'created_by' => $admin->id,
        ]);

        $fg4 = FunGame::create([
            'title' => 'Fun Game Bozz #12 - Tebet Masters',
            'event_date' => Carbon::now()->subWeek()->toDateString(),
            'status' => FunGameStatus::Completed,
            'champion_id' => $aprPinky->id,
            'runner_up_id' => $pedong->id,
            'third_place_id' => $nina->id,
            'notes' => 'Event penentuan evaluasi HC bulan ini.',
            'created_by' => $admin->id,
        ]);

        $fgActive = FunGame::create([
            'title' => 'Fun Game Bozz #13 - Special Edition',
            'event_date' => Carbon::now()->toDateString(),
            'status' => FunGameStatus::Open,
            'notes' => 'Event live saat ini, silakan input pertandingan.',
            'created_by' => $admin->id,
        ]);

        // 5. Seed Matches in FG1-FG4 (including 5 H2H matches between Apr Pinky and Pedong)
        // H2H Matches Apr Pinky vs Pedong: 3 wins Apr Pinky, 2 wins Pedong
        MatchLog::create([
            'fun_game_id' => $fg1->id,
            'player1_id' => $aprPinky->id,
            'player2_id' => $pedong->id,
            'player1_score' => 2,
            'player2_score' => 3,
            'winner_id' => $pedong->id,
            'player1_hc' => Handicap::ThreeA,
            'player2_hc' => Handicap::ThreeA,
            'stage' => 'Final',
            'recorded_by' => $admin->id,
        ]);

        MatchLog::create([
            'fun_game_id' => $fg2->id,
            'player1_id' => $pedong->id,
            'player2_id' => $aprPinky->id,
            'player1_score' => 3,
            'player2_score' => 1,
            'winner_id' => $pedong->id,
            'player1_hc' => Handicap::ThreeA,
            'player2_hc' => Handicap::ThreeA,
            'stage' => 'Semifinal',
            'recorded_by' => $admin->id,
        ]);

        MatchLog::create([
            'fun_game_id' => $fg3->id,
            'player1_id' => $aprPinky->id,
            'player2_id' => $pedong->id,
            'player1_score' => 3,
            'player2_score' => 1,
            'winner_id' => $aprPinky->id,
            'player1_hc' => Handicap::ThreeA,
            'player2_hc' => Handicap::ThreeA,
            'stage' => 'Babak Penyisihan',
            'recorded_by' => $admin->id,
        ]);

        MatchLog::create([
            'fun_game_id' => $fg4->id,
            'player1_id' => $aprPinky->id,
            'player2_id' => $pedong->id,
            'player1_score' => 3,
            'player2_score' => 2,
            'winner_id' => $aprPinky->id,
            'player1_hc' => Handicap::ThreeA,
            'player2_hc' => Handicap::ThreeA,
            'stage' => 'Final',
            'recorded_by' => $admin->id,
        ]);

        MatchLog::create([
            'fun_game_id' => $fg4->id,
            'player1_id' => $pedong->id,
            'player2_id' => $aprPinky->id,
            'player1_score' => 1,
            'player2_score' => 3,
            'winner_id' => $aprPinky->id,
            'player1_hc' => Handicap::ThreeA,
            'player2_hc' => Handicap::ThreeA,
            'stage' => 'Babak Penyisihan',
            'recorded_by' => $admin->id,
        ]);

        // More matches to get Apr Pinky to ~25 matches and ~68% win rate (17 wins out of 25 = 68%)
        $opponents = [$fadil, $nina, $budi, $rian];
        $matchPairs = [
            [$admin, true, 3, 2, $fg1], // win vs 3+ (Giant killer 1)
            [$nina, true, 3, 0, $fg1],
            [$budi, false, 2, 3, $fg1], // loss
            [$rian, true, 3, 0, $fg1],

            [$admin, true, 3, 1, $fg2], // win vs 3+ (Giant killer 2)
            [$nina, false, 2, 3, $fg2], // loss
            [$budi, true, 3, 1, $fg2],
            [$rian, true, 3, 0, $fg2],

            [$admin, true, 3, 2, $fg3], // win vs 3+ (Giant killer 3)
            [$nina, true, 3, 1, $fg3],
            [$budi, false, 1, 3, $fg3], // loss
            [$rian, true, 3, 0, $fg3],
            [$fadil, true, 3, 2, $fg3],

            [$admin, true, 3, 1, $fg4], // win vs 3+ (Giant killer 4)
            [$nina, true, 3, 1, $fg4],
            [$budi, true, 3, 0, $fg4],
            [$rian, true, 3, 1, $fg4],
            [$fadil, false, 1, 3, $fg4], // loss
            [$budi, false, 2, 3, $fg4], // loss
            [$nina, false, 1, 3, $fg4], // loss
        ];

        foreach ($matchPairs as $pair) {
            [$opp, $isAprWin, $aprScore, $oppScore, $ev] = $pair;
            MatchLog::create([
                'fun_game_id' => $ev->id,
                'player1_id' => $aprPinky->id,
                'player2_id' => $opp->id,
                'player1_score' => $aprScore,
                'player2_score' => $oppScore,
                'winner_id' => $isAprWin ? $aprPinky->id : $opp->id,
                'player1_hc' => Handicap::ThreeA,
                'player2_hc' => $opp->handicap,
                'stage' => 'Babak Penyisihan',
                'recorded_by' => $admin->id,
            ]);
        }

        // 6. Seed Solo Drills
        PlayerDrill::create([
            'user_id' => $aprPinky->id,
            'drill_name' => 'Straight Shot 10x Corner Pocket',
            'success_count' => 8,
            'fail_count' => 2,
            'practice_date' => Carbon::now()->subDays(1)->toDateString(),
            'notes' => 'Fokus konsistensi ayunan lengan dan follow-through lurus.',
        ]);

        PlayerDrill::create([
            'user_id' => $aprPinky->id,
            'drill_name' => 'Cut Shot Angle 45 Derajat',
            'success_count' => 7,
            'fail_count' => 3,
            'practice_date' => Carbon::now()->subDays(3)->toDateString(),
            'notes' => 'Masih agak tipis di percobaan awal.',
        ]);

        PlayerDrill::create([
            'user_id' => $aprPinky->id,
            'drill_name' => 'Draw Shot Stun Ball Control',
            'success_count' => 9,
            'fail_count' => 1,
            'practice_date' => Carbon::now()->subDays(6)->toDateString(),
            'notes' => 'Kontrol cue ball mundur ke spot sangat presisi.',
        ]);

        // Drills for Fadil
        PlayerDrill::create([
            'user_id' => $fadil->id,
            'drill_name' => 'Straight Long Potting',
            'success_count' => 6,
            'fail_count' => 4,
            'practice_date' => Carbon::now()->subDays(2)->toDateString(),
            'notes' => 'Perbaiki tumpuan jembatan tangan (open bridge).',
        ]);

        // 7. Seed HC History for Apr Pinky & Pedong
        HcHistory::create([
            'user_id' => $aprPinky->id,
            'old_hc' => Handicap::ThreeN,
            'new_hc' => Handicap::ThreeA,
            'reason' => 'Evaluasi 4 Fun Game Resmi: Win rate konsisten 68% dan sering menundukkan pemain HC 3A.',
            'changed_by' => $admin->id,
            'approved_at' => Carbon::now()->subWeeks(3),
        ]);

        HcHistory::create([
            'user_id' => $pedong->id,
            'old_hc' => Handicap::ThreeN,
            'new_hc' => Handicap::ThreeA,
            'reason' => 'Juara Fun Game Bozz #9 dan konsistensi game race to 3.',
            'changed_by' => $admin->id,
            'approved_at' => Carbon::now()->subWeeks(4),
        ]);
    }
}
