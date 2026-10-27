<?php

namespace App\Http\Controllers;

use App\Enums\FunGameStatus;
use App\Enums\Handicap;
use App\Enums\UserRole;
use App\Models\FunGame;
use App\Models\HcHistory;
use App\Models\User;
use App\WinRateRanking;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RosterController extends Controller
{
    /**
     * Display Community Roster & Leaderboard.
     */
    public function index(Request $request): View
    {
        $request->validate([
            'tab' => ['nullable', 'string', 'max:30'],
            'sort' => ['nullable', 'string', 'max:100'],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $tab = $request->query('tab', 'leaderboard');
        $sort = $request->query('sort', 'most_podium');
        if (! in_array($sort, ['most_podium', 'win_rate', 'most_active', 'most_drills'], true)) {
            $sort = 'most_podium';
        }
        $search = $request->query('search', '');

        $membersQuery = User::query();

        if ($search) {
            $membersQuery->where('name', 'like', "%{$search}%");
        }

        $allMembers = $membersQuery->get();
        $podiumCounts = $this->podiumCounts();
        $winRateStandings = WinRateRanking::standings();

        // 1. Compute stats for each member
        $rankedMembers = $allMembers->map(function (User $m) use ($podiumCounts, $winRateStandings): array {
            $totalMatches = $m->totalMatchesCount();
            $wins = $m->totalWinsCount();
            $winRate = $m->winRatePercentage();
            $drillsCount = $m->playerDrills()->count();
            $placements = $podiumCounts[$m->id] ?? [1 => 0, 2 => 0, 3 => 0];
            $standing = $winRateStandings->get($m->id, ['total' => 0, 'wins' => 0, 'events' => 0, 'win_rate' => 0.0, 'score' => 0.0, 'eligible' => false]);

            return [
                'user' => $m,
                'total_matches' => $totalMatches,
                'wins' => $wins,
                'win_rate' => $winRate,
                'drills_count' => $drillsCount,
                'podium_count' => array_sum($placements),
                'champion_count' => $placements[1],
                'runner_up_count' => $placements[2],
                'third_place_count' => $placements[3],
                'ranking_matches' => $standing['total'],
                'ranking_wins' => $standing['wins'],
                'ranking_events' => $standing['events'],
                'ranking_win_rate' => $standing['win_rate'],
                'ranking_score' => $standing['score'],
                'ranking_eligible' => $standing['eligible'],
            ];
        });

        // 2. Sort according to filter
        $sortedRankings = match ($sort) {
            'most_active' => $rankedMembers->sortByDesc('total_matches')->values(),
            'most_drills' => $rankedMembers->sortByDesc('drills_count')->values(),
            'win_rate' => $rankedMembers->where('ranking_eligible', true)->sortBy([
                ['ranking_score', 'desc'], ['ranking_matches', 'desc'], ['user.name', 'asc'], ['user.id', 'asc'],
            ])->values(),
            default => $rankedMembers->sortBy([
                ['podium_count', 'desc'],
                ['champion_count', 'desc'],
                ['runner_up_count', 'desc'],
                ['user.name', 'asc'],
                ['user.id', 'asc'],
            ])->values(),
        };

        // Top 3 for Podium
        $podium = [
            'first' => $sortedRankings->get(0),
            'second' => $sortedRankings->get(1),
            'third' => $sortedRankings->get(2),
        ];

        // Rest of players (Rank 4+)
        $restRankings = $sortedRankings->slice(3)->values();

        // 3. Group by Handicap for the Roster tab
        $handicapTiers = [
            Handicap::ThreePlus,
            Handicap::ThreeA,
            Handicap::ThreeN,
            Handicap::ThreeB,
            Handicap::ThreeSb,
        ];

        $rosterByHandicap = [];
        foreach ($handicapTiers as $hc) {
            $membersInHc = $rankedMembers->filter(fn ($item): bool => $item['user']->handicap === $hc)->values();
            $rosterByHandicap[$hc->value] = [
                'handicap' => $hc,
                'count' => $membersInHc->count(),
                'members' => $membersInHc,
            ];
        }

        return view('roster.index', [
            'tab' => $tab,
            'sort' => $sort,
            'search' => $search,
            'searchPlayers' => User::query()->orderBy('name')->get(['id', 'name', 'handicap']),
            'podium' => $podium,
            'restRankings' => $restRankings,
            'unrankedMembers' => $sort === 'win_rate' ? $rankedMembers->where('ranking_eligible', false)->sortBy([['ranking_matches', 'desc'], ['user.name', 'asc']])->values() : collect(),
            'rosterByHandicap' => $rosterByHandicap,
            'initialHandicap' => $request->user()->handicap->value,
            'totalMembers' => $allMembers->count(),
        ]);
    }

    /**
     * Count each player's recorded placement once per completed event, including doubles.
     *
     * @return array<int, array{1: int, 2: int, 3: int}>
     */
    private function podiumCounts(): array
    {
        $counts = [];
        $events = FunGame::query()->where('status', FunGameStatus::Completed)
            ->with('podiumPlayers:id')
            ->get(['id', 'champion_id', 'runner_up_id', 'third_place_id']);

        foreach ($events as $event) {
            $placements = [];
            foreach ([1 => $event->champion_id, 2 => $event->runner_up_id, 3 => $event->third_place_id] as $position => $playerId) {
                if ($playerId) {
                    $placements[$playerId] = min($placements[$playerId] ?? $position, $position);
                }
            }
            foreach ($event->podiumPlayers as $player) {
                $position = (int) $player->pivot->position;
                if (in_array($position, [1, 2, 3], true)) {
                    $placements[$player->id] = min($placements[$player->id] ?? $position, $position);
                }
            }
            foreach ($placements as $playerId => $position) {
                $counts[$playerId] ??= [1 => 0, 2 => 0, 3 => 0];
                $counts[$playerId][$position]++;
            }
        }

        return $counts;
    }

    /**
     * Show a community member's player passport.
     */
    public function show(User $user): View
    {
        $totalMatches = $user->totalMatchesCount();
        $wins = $user->totalWinsCount();

        $recentMatches = $user->allMatches()
            ->with(['player1', 'player2', 'winner', 'funGame', 'team1.player1', 'team1.player2', 'team2.player1', 'team2.player2', 'winnerTeam'])
            ->latest()
            ->latest('id')
            ->take(5)
            ->get();

        return view('roster.show', [
            'member' => $user,
            'stats' => [
                'win_rate' => $totalMatches > 0 ? round(($wins / $totalMatches) * 100, 1) : 0.0,
                'total_matches' => $totalMatches,
                'wins' => $wins,
                'total_losses' => max(0, $totalMatches - $wins),
                'fun_games_count' => $user->funGamesParticipatedCount(),
            ],
            'badges' => $user->badges()->get(),
            'recentMatches' => $recentMatches,
        ]);
    }

    /**
     * Issue a private, one-time activation code for an inactive member.
     */
    public function issueActivationCode(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()->canManageAccount($user), 403);

        $code = (string) random_int(10_000_000, 99_999_999);
        DB::transaction(function () use ($request, $user, $code): void {
            $target = User::query()->lockForUpdate()->findOrFail($user->id);
            abort_unless($request->user()->fresh()->canManageAccount($target), 403);
            if ($target->is_active) {
                throw ValidationException::withMessages(['activation_code' => 'Akun sudah aktif dan tidak memerlukan kode aktivasi.']);
            }

            $target->forceFill([
                'activation_code_hash' => Hash::make($code),
                'activation_code_expires_at' => now()->addDay(),
            ])->save();
        });

        return redirect()->route('roster.show', $user)->with('issued_activation_code', [
            'user_id' => $user->id,
            'code' => $code,
        ]);
    }

    /**
     * Set a new password for an activated community member (Admin).
     */
    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        if (! Auth::user()?->isAdmin()) {
            abort(403, 'Akses terbatas untuk Pengurus BozzBill.');
        }

        abort_unless(Auth::user()->canManageAccount($user), 403);
        abort_if(Auth::id() === $user->id, 403, 'Gunakan pengaturan password di profil Anda sendiri.');

        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'password' => 'Akun belum diaktifkan. Member perlu melakukan aktivasi akun terlebih dahulu.',
            ]);
        }

        $validated = $request->validate([
            'password' => ['required', 'string', 'confirmed', Password::min(6)],
        ], [
            'password.required' => 'Password baru wajib diisi.',
            'password.string' => 'Password baru harus berupa teks.',
            'password.min' => 'Password baru minimal harus 6 karakter.',
            'password.confirmed' => 'Konfirmasi password baru tidak cocok.',
        ]);

        DB::transaction(function () use ($user, $validated): void {
            $user = User::query()->lockForUpdate()->findOrFail($user->id);
            abort_unless(Auth::user()->fresh()->canManageAccount($user), 403);
            $user->forceFill([
                'password' => Hash::make($validated['password']),
            ])->save();
            $user->revokeLoginSessions();
        });

        return redirect()
            ->route('roster.show', $user)
            ->with('status_success', "Password {$user->name} berhasil direset. Member dapat masuk dengan password baru.");
    }

    /**
     * Register a new community member (Admin).
     */
    public function storeMember(Request $request): RedirectResponse
    {
        if (! Auth::user()?->isAdmin()) {
            abort(403, 'Akses terbatas untuk Pengurus BozzBill.');
        }

        if (is_string($request->input('phone'))) {
            $request->merge(['phone' => User::normalizePhone($request->input('phone'))]);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'regex:/^08[0-9]{8,13}$/', 'unique:users,phone'],
            'handicap' => ['required', Rule::enum(Handicap::class)],
        ], [
            'name.required' => 'Nama lengkap pemain wajib diisi.',
            'phone.required' => 'Nomor WhatsApp pemain wajib diisi.',
            'phone.unique' => 'Nomor WhatsApp ini sudah terdaftar sebelumnya.',
            'handicap.required' => 'Level Handicap awal wajib dipilih.',
        ]);

        $newMember = User::create([
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'handicap' => Handicap::from($validated['handicap']),
            'role' => UserRole::Member,
            'is_active' => false, // Member will activate themselves by creating a password
            'password' => null,
        ]);

        return redirect()
            ->route('roster', ['tab' => 'roster'])
            ->with('status_success', "Calon member '{$newMember->name}' ({$newMember->phone}) berhasil didaftarkan dengan HC {$newMember->handicap->value}! Buka profil anggota untuk membuat kode aktivasi.");
    }

    public function updateHandicap(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

        $validated = $request->validate([
            'handicap' => ['required', Rule::enum(Handicap::class)],
            'reason' => ['required', 'string', 'max:500'],
        ], [
            'handicap.required' => 'Pilih HC pemain.',
            'handicap.enum' => 'Pilih level HC yang tersedia.',
            'reason.required' => 'Alasan perubahan HC wajib diisi.',
        ]);

        $changed = DB::transaction(function () use ($request, $user, $validated): bool {
            $target = User::query()->lockForUpdate()->findOrFail($user->id);
            abort_unless($request->user()->fresh()->isAdmin(), 403);
            $newHandicap = Handicap::from($validated['handicap']);

            if ($target->handicap === $newHandicap) {
                return false;
            }

            HcHistory::create([
                'user_id' => $target->id,
                'old_hc' => $target->handicap,
                'new_hc' => $newHandicap,
                'reason' => $validated['reason'],
                'changed_by' => $request->user()->id,
                'approved_at' => now(),
            ]);
            $target->update(['handicap' => $newHandicap]);

            return true;
        });

        return redirect()->route('roster.show', $user)->with('status_success', $changed
            ? 'HC pemain berhasil diperbarui dan dicatat dalam riwayat. HC pada match sebelumnya tetap tersimpan.'
            : 'HC pemain sudah sesuai. Tidak ada perubahan.');
    }

    public function updatePhone(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()->canManageAccount($user), 403);

        if (is_string($request->input('phone'))) {
            $request->merge(['phone' => User::normalizePhone($request->input('phone'))]);
        }

        $validated = $request->validate([
            'phone' => ['required', 'string', 'regex:/^08[0-9]{8,13}$/', Rule::unique('users', 'phone')->ignore($user)],
        ], [
            'phone.required' => 'Nomor WhatsApp wajib diisi.',
            'phone.regex' => 'Gunakan nomor WhatsApp Indonesia yang valid, misalnya 081234567890.',
            'phone.unique' => 'Nomor WhatsApp ini sudah digunakan akun lain.',
        ]);

        DB::transaction(function () use ($request, $user, $validated): void {
            $target = User::query()->lockForUpdate()->findOrFail($user->id);
            abort_unless($request->user()->fresh()->canManageAccount($target), 403);

            if ($target->phone !== $validated['phone']) {
                $this->recordAccountChange($target, 'phone', $target->phone, $validated['phone'], $request->user()->id);
                $target->forceFill([
                    'phone' => $validated['phone'],
                    'activation_code_hash' => null,
                    'activation_code_expires_at' => null,
                ])->save();
                $target->revokeLoginSessions();
            }
        });

        if ($request->user()->is($user) && $user->phone !== $validated['phone']) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('status_success', 'Nomor WhatsApp diperbarui. Silakan masuk menggunakan nomor baru.');
        }

        return redirect()->route('roster.show', $user)
            ->with('status_success', 'Nomor WhatsApp diperbarui. Gunakan nomor baru untuk masuk; histori pemain tetap tersimpan.');
    }

    public function updateRole(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()->isSuperadmin() && ! $user->isSuperadmin(), 403);

        $validated = $request->validate([
            'role' => ['required', Rule::in([UserRole::Admin->value, UserRole::Member->value])],
        ]);

        DB::transaction(function () use ($request, $user, $validated): void {
            $target = User::query()->lockForUpdate()->findOrFail($user->id);
            abort_unless($request->user()->fresh()->isSuperadmin() && ! $target->isSuperadmin(), 403);

            if ($target->role->value !== $validated['role']) {
                $this->recordAccountChange($target, 'role', $target->role->value, $validated['role'], $request->user()->id);
                $target->update(['role' => $validated['role']]);
                $target->revokeLoginSessions();
            }
        });

        return redirect()->route('roster.show', $user)
            ->with('status_success', 'Jabatan akun berhasil diperbarui. Pemain perlu masuk kembali; histori tetap tersimpan.');
    }

    private function recordAccountChange(User $target, string $field, ?string $oldValue, string $newValue, int $changedBy): void
    {
        DB::table('account_changes')->insert([
            'user_id' => $target->id,
            'changed_by' => $changedBy,
            'field' => $field,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
