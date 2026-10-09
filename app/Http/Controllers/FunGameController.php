<?php

namespace App\Http\Controllers;

use App\Enums\FunGameStatus;
use App\Models\Badge;
use App\Models\FunGame;
use App\Models\MatchLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FunGameController extends Controller
{
    /**
     * Display list of all Fun Game events and archives.
     */
    public function index(Request $request): View
    {
        $activeEvent = FunGame::where('status', FunGameStatus::Open)
            ->with(['matches.player1', 'matches.player2', 'matches.winner'])
            ->latest('event_date')
            ->first();

        $completedEvents = FunGame::where('status', FunGameStatus::Completed)
            ->with(['champion', 'runnerUp', 'thirdPlace'])
            ->withCount('matches')
            ->orderByDesc('event_date')
            ->paginate(10);

        return view('fun-games.index', [
            'activeEvent' => $activeEvent,
            'completedEvents' => $completedEvents,
        ]);
    }

    /**
     * Show detail of a specific Fun Game event.
     */
    public function show(FunGame $funGame): View
    {
        $funGame->load([
            'champion',
            'runnerUp',
            'thirdPlace',
            'matches' => function ($q): void {
                $q->with(['player1', 'player2', 'winner'])->latest();
            },
        ]);

        $activeMembers = User::where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('fun-games.show', [
            'funGame' => $funGame,
            'members' => $activeMembers,
        ]);
    }

    /**
     * Dedicated Quick Match Logger screen (for admins standing by the table).
     */
    public function quickLogger(Request $request): View|RedirectResponse
    {
        if (! Auth::user()->isAdmin()) {
            abort(403, 'Akses terbatas hanya untuk pengurus.');
        }

        $activeEvent = FunGame::where('status', FunGameStatus::Open)->first();

        if (! $activeEvent) {
            return redirect()
                ->route('fun-games.index')
                ->with('status_warning', 'Tidak ada event Fun Game yang sedang aktif. Silakan buka event baru terlebih dahulu.');
        }

        $activeMembers = User::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'handicap']);

        $recentMatches = MatchLog::where('fun_game_id', $activeEvent->id)
            ->with(['player1', 'player2', 'winner'])
            ->latest()
            ->take(10)
            ->get();

        return view('fun-games.quick-logger', [
            'event' => $activeEvent,
            'members' => $activeMembers,
            'recentMatches' => $recentMatches,
        ]);
    }

    /**
     * Store a newly created Fun Game event (Admin).
     */
    public function store(Request $request): RedirectResponse
    {
        if (! Auth::user()->isAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'event_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $funGame = FunGame::create([
            'title' => $validated['title'],
            'event_date' => $validated['event_date'],
            'notes' => $validated['notes'] ?? null,
            'status' => FunGameStatus::Open,
            'created_by' => Auth::id(),
        ]);

        return redirect()
            ->route('fun-games.show', $funGame)
            ->with('status_success', "Event '{$funGame->title}' berhasil dibuka! Silakan catat pertandingan.");
    }

    /**
     * Record a new match in the event (Admin Quick Match Logger).
     */
    public function storeMatch(Request $request, FunGame $funGame): RedirectResponse
    {
        if (! Auth::user()->isAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'player1_id' => ['required', 'exists:users,id'],
            'player2_id' => ['required', 'exists:users,id', 'different:player1_id'],
            'player1_score' => ['required', 'integer', 'min:0', 'max:50'],
            'player2_score' => ['required', 'integer', 'min:0', 'max:50'],
            'winner_id' => ['required', Rule::in([$request->player1_id, $request->player2_id])],
            'stage' => ['nullable', 'string', 'max:50'],
        ], [
            'player2_id.different' => 'Pemain 1 dan Pemain 2 tidak boleh sama.',
            'winner_id.in' => 'Pemenang harus salah satu dari Pemain 1 atau Pemain 2.',
        ]);

        $p1 = User::findOrFail($validated['player1_id']);
        $p2 = User::findOrFail($validated['player2_id']);

        $match = MatchLog::create([
            'fun_game_id' => $funGame->id,
            'player1_id' => $p1->id,
            'player2_id' => $p2->id,
            'player1_score' => $validated['player1_score'],
            'player2_score' => $validated['player2_score'],
            'winner_id' => $validated['winner_id'],
            'player1_hc' => $p1->handicap,
            'player2_hc' => $p2->handicap,
            'stage' => $validated['stage'] ?: 'Penyisihan',
            'recorded_by' => Auth::id(),
        ]);

        // Award Giant Killer badge if winner beat higher HC
        if ($match->isGiantKiller()) {
            $winner = User::find($match->winner_id);
            $giantKillerBadge = Badge::where('slug', 'giant-killer')->first();
            if ($winner && $giantKillerBadge && ! $winner->badges()->where('badge_id', $giantKillerBadge->id)->exists()) {
                $winner->badges()->attach($giantKillerBadge->id);
            }
        }

        return back()->with('status_success', "Match berhasil dicatat: {$match->winner->name} menang ({$match->scoreSummary()})!");
    }

    /**
     * Close the event and assign Champion, Runner-up, and 3rd place (Admin).
     */
    public function closeEvent(Request $request, FunGame $funGame): RedirectResponse
    {
        if (! Auth::user()->isAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'champion_id' => ['required', 'exists:users,id'],
            'runner_up_id' => ['nullable', 'exists:users,id'],
            'third_place_id' => ['nullable', 'exists:users,id'],
        ]);

        $funGame->update([
            'status' => FunGameStatus::Completed,
            'champion_id' => $validated['champion_id'],
            'runner_up_id' => $validated['runner_up_id'] ?: null,
            'third_place_id' => $validated['third_place_id'] ?: null,
        ]);

        // Award Champion badge to winner
        $champion = User::find($validated['champion_id']);
        $championBadge = Badge::where('slug', 'champion')->first();
        if ($champion && $championBadge && ! $champion->badges()->where('badge_id', $championBadge->id)->exists()) {
            $champion->badges()->attach($championBadge->id);
        }

        return redirect()
            ->route('fun-games.show', $funGame)
            ->with('status_success', "Event '{$funGame->title}' resmi ditutup. Juara telah dinobatkan!");
    }
}
