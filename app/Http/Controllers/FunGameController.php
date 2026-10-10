<?php

namespace App\Http\Controllers;

use App\Enums\FunGameStatus;
use App\Models\Badge;
use App\Models\FunGame;
use App\Models\MatchLog;
use App\Models\User;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator as ValidatorInstance;
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
            ->latest('id')
            ->first();

        $completedEvents = FunGame::whereIn('status', [FunGameStatus::Completed, FunGameStatus::Cancelled])
            ->with(['champion', 'runnerUp', 'thirdPlace', 'podiumPlayers'])
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
            'podiumPlayers',
            'matches' => function ($q): void {
                $q->with(['player1', 'player2', 'winner'])->latest();
            },
        ]);

        $members = User::query()
            ->orderBy('name')
            ->get();

        return view('fun-games.show', [
            'funGame' => $funGame,
            'members' => $members,
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

        $activeEvent = FunGame::where('status', FunGameStatus::Open)
            ->latest('event_date')
            ->latest('id')
            ->first();

        if (! $activeEvent) {
            return redirect()
                ->route('fun-games.index')
                ->with('status_warning', 'Tidak ada event Fun Game yang sedang aktif. Silakan buka event baru terlebih dahulu.');
        }

        $members = User::query()
            ->orderBy('name')
            ->get(['id', 'name', 'handicap']);

        $recentMatches = MatchLog::where('fun_game_id', $activeEvent->id)
            ->with(['player1', 'player2', 'winner'])
            ->latest()
            ->take(10)
            ->get();

        return view('fun-games.quick-logger', [
            'event' => $activeEvent,
            'members' => $members,
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

        try {
            $funGame = Cache::lock('fun-games:open', 10)->block(5, function () use ($validated): FunGame {
                if (FunGame::where('status', FunGameStatus::Open)->exists()) {
                    throw ValidationException::withMessages([
                        'title' => 'Masih ada Fun Game aktif. Tutup event tersebut sebelum membuka event baru.',
                    ]);
                }

                return FunGame::create([
                    'title' => $validated['title'],
                    'event_date' => $validated['event_date'],
                    'notes' => $validated['notes'] ?? null,
                    'status' => FunGameStatus::Open,
                    'created_by' => Auth::id(),
                ]);
            });
        } catch (LockTimeoutException) {
            throw ValidationException::withMessages([
                'title' => 'Pembukaan event sedang diproses pengurus lain. Silakan coba kembali.',
            ]);
        }

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

        $validator = Validator::make($request->all(), [
            'player1_id' => ['bail', 'required', 'integer', Rule::exists('users', 'id')],
            'player2_id' => ['bail', 'required', 'integer', Rule::exists('users', 'id')],
            'player1_score' => ['required', 'integer', 'min:0', 'max:50'],
            'player2_score' => ['required', 'integer', 'min:0', 'max:50'],
            'winner_id' => ['bail', 'required', 'integer'],
            'stage' => ['nullable', 'string', 'max:50'],
        ], [
            'player2_id.different' => 'Pemain 1 dan Pemain 2 tidak boleh sama.',
            'winner_id.in' => 'Pemenang harus salah satu dari Pemain 1 atau Pemain 2.',
        ]);

        $validator->after(function (ValidatorInstance $validator) use ($request): void {
            if ($validator->errors()->hasAny(['player1_id', 'player2_id'])) {
                return;
            }

            if ((int) $request->input('player1_id') === (int) $request->input('player2_id')) {
                $validator->addFailure('player2_id', 'Different', ['player1_id']);
            }

            if (! $validator->errors()->has('winner_id') && ! in_array((int) $request->input('winner_id'), [
                (int) $request->input('player1_id'),
                (int) $request->input('player2_id'),
            ], true)) {
                $validator->addFailure('winner_id', 'In');
            }
        });

        $validated = $validator->validate();
        $validated['winner_id'] = (int) $validated['winner_id'];

        $p1 = User::findOrFail($validated['player1_id']);
        $p2 = User::findOrFail($validated['player2_id']);

        $match = DB::transaction(function () use ($funGame, $p1, $p2, $validated): MatchLog {
            $event = FunGame::query()->lockForUpdate()->findOrFail($funGame->id);

            if (! $event->isOpen()) {
                throw ValidationException::withMessages([
                    'fun_game' => 'Event sudah ditutup. Hasil pertandingan tidak dapat ditambahkan.',
                ]);
            }

            $match = MatchLog::create([
                'fun_game_id' => $event->id,
                'player1_id' => $p1->id,
                'player2_id' => $p2->id,
                'player1_score' => $validated['player1_score'],
                'player2_score' => $validated['player2_score'],
                'winner_id' => $validated['winner_id'],
                'player1_hc' => $p1->handicap,
                'player2_hc' => $p2->handicap,
                'stage' => $validated['stage'] ?? 'Penyisihan',
                'recorded_by' => Auth::id(),
            ]);

            if ($match->isGiantKiller()) {
                $winner = $match->winner;
                $giantKillerBadge = Badge::where('slug', 'giant-killer')->first();

                if ($giantKillerBadge && ! $winner->badges()->where('badge_id', $giantKillerBadge->id)->exists()) {
                    $winner->badges()->attach($giantKillerBadge->id);
                }
            }

            return $match;
        });

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

        $validator = Validator::make($request->all(), [
            'champion_id' => ['bail', 'required', 'integer', Rule::exists('users', 'id')],
            'runner_up_id' => ['bail', 'nullable', 'integer', Rule::exists('users', 'id')],
            'third_place_id' => ['bail', 'nullable', 'integer', Rule::exists('users', 'id')],
        ]);

        $validator->after(function (ValidatorInstance $validator) use ($request): void {
            $differentPositions = [
                'runner_up_id' => ['champion_id'],
                'third_place_id' => ['champion_id', 'runner_up_id'],
            ];

            foreach ($differentPositions as $position => $otherPositions) {
                $playerId = $request->input($position);

                if ($playerId === null || $validator->errors()->has($position)) {
                    continue;
                }

                foreach ($otherPositions as $otherPosition) {
                    $otherPlayerId = $request->input($otherPosition);

                    if ($otherPlayerId !== null && ! $validator->errors()->has($otherPosition) && (int) $playerId === (int) $otherPlayerId) {
                        $validator->addFailure($position, 'Different', [$otherPosition]);
                    }
                }
            }
        });

        $validated = $validator->validate();

        foreach (['champion_id', 'runner_up_id', 'third_place_id'] as $position) {
            if (isset($validated[$position])) {
                $validated[$position] = (int) $validated[$position];
            }
        }

        DB::transaction(function () use ($funGame, $validated): void {
            $event = FunGame::query()->lockForUpdate()->findOrFail($funGame->id);

            if (! $event->isOpen()) {
                throw ValidationException::withMessages([
                    'fun_game' => 'Event sudah ditutup. Hasil juara tidak dapat diubah melalui penutupan event.',
                ]);
            }

            $event->update([
                'status' => FunGameStatus::Completed,
                'champion_id' => $validated['champion_id'],
                'runner_up_id' => $validated['runner_up_id'] ?? null,
                'third_place_id' => $validated['third_place_id'] ?? null,
            ]);

            $champion = User::findOrFail($validated['champion_id']);
            $championBadge = Badge::where('slug', 'champion')->first();

            if ($championBadge && ! $champion->badges()->where('badge_id', $championBadge->id)->exists()) {
                $champion->badges()->attach($championBadge->id);
            }
        });

        return redirect()
            ->route('fun-games.show', $funGame)
            ->with('status_success', "Event '{$funGame->title}' resmi ditutup. Juara telah dinobatkan!");
    }
}
