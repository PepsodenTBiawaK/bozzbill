<?php

namespace App\Http\Controllers;

use App\Enums\Handicap;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
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
