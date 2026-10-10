<?php

namespace App\Http\Controllers;

use App\Enums\Handicap;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
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
        $tab = $request->query('tab', 'leaderboard');
        $sort = $request->query('sort', 'win_rate');
        $search = $request->query('search', '');

        // Fetch all active members
        $membersQuery = User::where('role', UserRole::Member)
            ->where('is_active', true);

        if ($search) {
            $membersQuery->where('name', 'like', "%{$search}%");
        }

        $allMembers = $membersQuery->get();

        // 1. Compute stats for each member
        $rankedMembers = $allMembers->map(function (User $m): array {
            $totalMatches = $m->totalMatchesCount();
            $wins = $m->totalWinsCount();
            $winRate = $m->winRatePercentage();
            $drillsCount = $m->playerDrills()->count();

            return [
                'user' => $m,
                'total_matches' => $totalMatches,
                'wins' => $wins,
                'win_rate' => $winRate,
                'drills_count' => $drillsCount,
            ];
        });

        // 2. Sort according to filter
        $sortedRankings = match ($sort) {
            'most_active' => $rankedMembers->sortByDesc('total_matches')->values(),
            'most_drills' => $rankedMembers->sortByDesc('drills_count')->values(),
            default => $rankedMembers->sortByDesc('win_rate')->values(),
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
            'podium' => $podium,
            'restRankings' => $restRankings,
            'rosterByHandicap' => $rosterByHandicap,
            'totalActiveMembers' => $allMembers->count(),
        ]);
    }

    /**
     * Show a community member's player passport.
     */
    public function show(User $user): View
    {
        abort_unless($user->isMember(), 404);
        abort_unless($user->is_active || Auth::user()->isAdmin(), 404);

        $totalMatches = $user->totalMatchesCount();
        $wins = $user->totalWinsCount();

        $recentMatches = $user->allMatches()
            ->with(['player1', 'player2', 'winner', 'funGame'])
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
     * Set a new password for an activated community member (Admin).
     */
    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        if (! Auth::user()?->isAdmin()) {
            abort(403, 'Akses terbatas untuk Pengurus BozzBill.');
        }

        abort_unless($user->isMember(), 404);

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
            $user->forceFill([
                'password' => Hash::make($validated['password']),
                'remember_token' => Str::random(60),
            ])->save();

            if (config('session.driver') === 'database') {
                DB::connection(config('session.connection'))
                    ->table(config('session.table', 'sessions'))
                    ->where('user_id', $user->id)
                    ->delete();
            }
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

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'min:8', 'max:20', 'unique:users,phone'],
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
            ->with('status_success', "Calon member '{$newMember->name}' ({$newMember->phone}) berhasil didaftarkan dengan HC {$newMember->handicap->value}! Member dapat langsung melakukan Aktivasi Akun.");
    }
}
