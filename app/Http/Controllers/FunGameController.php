<?php

namespace App\Http\Controllers;

use App\Enums\FunGameStatus;
use App\Enums\MatchVoor;
use App\Models\Badge;
use App\Models\FunGame;
use App\Models\FunGameTeam;
use App\Models\MatchLog;
use App\Models\User;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
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
            ->with(['matches.player1', 'matches.player2', 'matches.winner', 'matches.team1.player1', 'matches.team1.player2', 'matches.team2.player1', 'matches.team2.player2', 'matches.winnerTeam.player1', 'matches.winnerTeam.player2'])
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
            'teams' => fn (HasMany $query): HasMany => $query->with(['player1', 'player2'])->withCount(['matchesAsTeam1', 'matchesAsTeam2'])->orderBy('id'),
            'matches' => function (HasMany $q): void {
                $q->with(['player1', 'player2', 'winner', 'team1.player1', 'team1.player2', 'team2.player1', 'team2.player2', 'winnerTeam.player1', 'winnerTeam.player2'])->latest();
            },
        ]);

        if (Auth::user()->fresh()?->isSuperadmin()) {
            $funGame->load(['corrections' => fn (HasMany $query): HasMany => $query->with('actor:id,name')->latest('id')]);
        }

        $members = User::query()
            ->orderBy('name')
            ->get();

        return view('fun-games.show', [
            'funGame' => $funGame,
            'members' => $members,
            'podiumMembers' => User::withTrashed()->where(function (Builder $query) use ($funGame): void {
                $query->whereNull('deleted_at')->orWhereIn('id', $this->eventParticipantIds($funGame));
            })->orderBy('name')->get(),
            'teams' => $funGame->teams,
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
            ->with(['player1', 'player2', 'winner', 'team1.player1', 'team1.player2', 'team2.player1', 'team2.player2', 'winnerTeam.player1', 'winnerTeam.player2'])
            ->latest()
            ->take(10)
            ->get();

        $activeEvent->setRelation('teams', $activeEvent->teams()->with(['player1', 'player2'])->orderBy('id')->get()
            ->filter(fn (FunGameTeam $team): bool => ! $team->player1->trashed() && ! $team->player2->trashed())->values());

        return view('fun-games.quick-logger', [
            'event' => $activeEvent,
            'members' => $members,
            'recentMatches' => $recentMatches,
            'teams' => $activeEvent->teams()->with(['player1', 'player2'])->orderBy('id')->get(),
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
            'format' => ['sometimes', 'required', Rule::in(['single', 'double'])],
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
                    'format' => $validated['format'] ?? 'single',
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

    public function update(Request $request, FunGame $funGame): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'notes' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($request, $funGame, $validated): void {
            $event = FunGame::query()->lockForUpdate()->findOrFail($funGame->id);
            abort_unless($request->user()->fresh()->isAdmin(), 403);
            $event->update(['title' => $validated['title'], 'notes' => $validated['notes'] ?? null]);
        });

        return redirect()->route('fun-games.show', $funGame)->with('status_success', 'Judul dan deskripsi Fun Game berhasil diperbarui.');
    }

    public function storeTeam(Request $request, FunGame $funGame): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);
        $validated = $this->validatedTeam($request);

        DB::transaction(function () use ($request, $funGame, $validated): void {
            $event = FunGame::query()->lockForUpdate()->findOrFail($funGame->id);
            $this->ensureTeamsEditable($event);
            abort_unless($request->user()->fresh()->isAdmin(), 403);
            $this->ensurePlayersAvailable($event, $validated);
            $event->teams()->create($validated);
        });

        return back()->with('status_success', 'Pasangan berhasil ditambahkan.');
    }

    public function updateTeam(Request $request, FunGame $funGame, FunGameTeam $team): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);
        $validated = $this->validatedTeam($request);

        DB::transaction(function () use ($request, $funGame, $team, $validated): void {
            $event = FunGame::query()->lockForUpdate()->findOrFail($funGame->id);
            $this->ensureTeamsEditable($event);
            abort_unless($request->user()->fresh()->isAdmin(), 403);
            $target = $event->teams()->lockForUpdate()->findOrFail($team->id);
            $this->ensureTeamUnused($target);
            $this->ensurePlayersAvailable($event, $validated, $target);
            $target->update($validated);
        });

        return back()->with('status_success', 'Pasangan berhasil diperbarui.');
    }

    public function destroyTeam(Request $request, FunGame $funGame, FunGameTeam $team): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

        DB::transaction(function () use ($request, $funGame, $team): void {
            $event = FunGame::query()->lockForUpdate()->findOrFail($funGame->id);
            $this->ensureTeamsEditable($event);
            abort_unless($request->user()->fresh()->isAdmin(), 403);
            $target = $event->teams()->lockForUpdate()->findOrFail($team->id);
            $this->ensureTeamUnused($target);
            $target->delete();
        });

        return back()->with('status_success', 'Pasangan berhasil dihapus.');
    }

    private function ensureTeamsEditable(FunGame $funGame): void
    {
        if (! $funGame->isOpen() || ! $funGame->isDouble()) {
            throw ValidationException::withMessages(['fun_game' => 'Pasangan hanya dapat diatur pada Fun Game double yang masih berjalan.']);
        }
    }

    private function ensureTeamUnused(FunGameTeam $team): void
    {
        if ($team->hasMatches()) {
            throw ValidationException::withMessages(['team' => 'Pasangan yang sudah memiliki match tidak dapat diubah atau dihapus.']);
        }
    }

    /** @return array{player1_id: int, player2_id: int} */
    private function validatedTeam(Request $request): array
    {
        $validated = $request->validate([
            'player1_id' => ['bail', 'required', 'integer', Rule::exists('users', 'id')->withoutTrashed()],
            'player2_id' => ['bail', 'required', 'integer', Rule::exists('users', 'id')->withoutTrashed()],
        ]);
        $validated = array_map(fn (int|string $id): int => (int) $id, $validated);

        if ($validated['player1_id'] === $validated['player2_id']) {
            throw ValidationException::withMessages(['player2_id' => 'Satu pasangan harus terdiri dari dua anggota yang berbeda.']);
        }

        return $validated;
    }

    /** @param array{player1_id: int, player2_id: int} $players */
    private function ensurePlayersAvailable(FunGame $funGame, array $players, ?FunGameTeam $except = null): void
    {
        $available = User::query()->whereIn('id', array_values($players))->orderBy('id')->lockForUpdate()->get();
        if ($available->count() !== 2) {
            throw ValidationException::withMessages(['player1_id' => 'Anggota yang sudah dihapus tidak dapat dipilih untuk pasangan baru.']);
        }

        foreach ($players as $field => $playerId) {
            $exists = $funGame->teams()->when($except !== null, fn (Builder $query): Builder => $query->whereKeyNot($except->id))
                ->where(fn (Builder $query): Builder => $query->where('player1_id', $playerId)->orWhere('player2_id', $playerId))->exists();

            if ($exists) {
                throw ValidationException::withMessages([$field => 'Anggota ini sudah berada dalam pasangan lain di Fun Game ini.']);
            }
        }
    }

    /**
     * Record a new match in the event (Admin Quick Match Logger).
     */
    public function storeMatch(Request $request, FunGame $funGame): RedirectResponse
    {
        if (! Auth::user()->isAdmin()) {
            abort(403);
        }

        $validated = $this->validatedMatch($request, $funGame);

        $match = DB::transaction(function () use ($request, $funGame, $validated): MatchLog {
            $event = FunGame::query()->lockForUpdate()->findOrFail($funGame->id);
            abort_unless($request->user()->fresh()->isAdmin(), 403);

            if (! $event->isOpen()) {
                throw ValidationException::withMessages([
                    'fun_game' => 'Event sudah ditutup. Hasil pertandingan tidak dapat ditambahkan.',
                ]);
            }

            $match = MatchLog::create([
                ...$this->matchAttributes($event, $validated),
                'fun_game_id' => $event->id,
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

        return back()->with('status_success', "Match berhasil dicatat: {$match->winnerName()} menang ({$match->scoreSummary()})!");
    }

    public function editMatch(Request $request, FunGame $funGame, MatchLog $match): View
    {
        $actor = $request->user()->fresh();
        abort_unless($actor?->isAdmin(), 403);
        abort_unless($funGame->isOpen() || ($funGame->isCompleted() && $actor->isSuperadmin()), 403, 'Event sudah ditutup. Koreksi match hanya tersedia untuk superadmin pada event yang selesai.');

        $funGame->loadMissing(['teams.player1', 'teams.player2']);
        $funGame->setRelation('teams', $funGame->teams->filter(fn (FunGameTeam $team): bool => in_array($team->id, [$match->team1_id, $match->team2_id], true)
            || (! $team->player1->trashed() && ! $team->player2->trashed()))->values());

        return view('fun-games.edit-match', [
            'funGame' => $funGame,
            'match' => $match->load(['player1', 'player2', 'team1.player1', 'team1.player2', 'team2.player1', 'team2.player2']),
            'members' => User::withTrashed()->where(function (Builder $query) use ($match): void {
                $query->whereNull('deleted_at')->orWhereIn('id', [$match->player1_id, $match->player2_id]);
            })->orderBy('name')->get(),
            'teams' => $funGame->teams,
        ]);
    }

    public function updateMatch(Request $request, FunGame $funGame, MatchLog $match): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);
        $passwordRejected = false;
        $completedCorrection = false;
        $rateLimitKey = $this->correctionRateLimitKey($request);

        try {
            DB::transaction(function () use ($request, $funGame, $match, &$passwordRejected, &$completedCorrection): void {
                $event = FunGame::query()->lockForUpdate()->findOrFail($funGame->id);
                $actor = User::query()->lockForUpdate()->findOrFail($request->user()->id);
                abort_unless($actor->isAdmin(), 403);
                $target = $event->matches()->lockForUpdate()->findOrFail($match->id);

                if ($event->isCompleted()) {
                    abort_unless($actor->isSuperadmin(), 403);
                    $this->correctCompletedMatch($request, $event, $target, $actor, $passwordRejected);
                    $completedCorrection = true;

                    return;
                }

                $this->ensureMatchesEditable($event);
                $validated = $this->validatedMatch($request, $event);
                $oldWinnerId = $target->winner_id;
                $target->update($this->matchAttributes($event, $validated, $target));

                foreach (User::query()->whereIn('id', array_filter(array_unique([$oldWinnerId, $target->winner_id])))
                    ->orderBy('id')->lockForUpdate()->get() as $winner) {
                    $this->syncGiantKillerBadge($winner);
                }
            }, 3);
        } catch (ValidationException $exception) {
            if ($passwordRejected) {
                RateLimiter::hit($rateLimitKey, 60);
            }

            throw $exception;
        }

        if ($completedCorrection) {
            RateLimiter::clear($rateLimitKey);
        }

        return redirect()->route('fun-games.show', $funGame)->with('status_success', $completedCorrection
            ? 'Koreksi match berhasil disimpan dan dicatat. Statistik serta lencana pemain sudah disesuaikan; podium event tetap.'
            : 'Match berhasil diperbarui. Statistik pemain sudah disesuaikan.');
    }

    private function correctCompletedMatch(Request $request, FunGame $event, MatchLog $target, User $actor, bool &$passwordRejected): void
    {
        if (is_string($request->input('correction_reason'))) {
            $request->merge(['correction_reason' => trim($request->input('correction_reason'))]);
        }

        $security = $request->validate([
            'current_password' => ['required', 'string'],
            'correction_reason' => ['required', 'string', 'max:500'],
            'correction_confirmed' => ['accepted'],
            'match_version' => ['required', 'string', 'size:64'],
        ], [
            'current_password.required' => 'Masukkan password akun Anda untuk menyimpan koreksi.',
            'correction_reason.required' => 'Tulis alasan koreksi match.',
            'correction_reason.max' => 'Alasan koreksi maksimal 500 karakter.',
            'correction_confirmed.accepted' => 'Konfirmasikan bahwa Anda yakin ingin mengoreksi match ini.',
            'match_version.required' => 'Muat ulang formulir koreksi untuk memastikan data terbaru.',
        ]);

        $rateLimitKey = $this->correctionRateLimitKey($request);
        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            throw ValidationException::withMessages([
                'current_password' => 'Terlalu banyak percobaan password. Coba kembali dalam '.RateLimiter::availableIn($rateLimitKey).' detik.',
            ])->status(429);
        }

        if (! Hash::check($security['current_password'], $actor->password ?? '')) {
            $passwordRejected = true;

            throw ValidationException::withMessages(['current_password' => 'Password akun Anda tidak cocok. Koreksi belum disimpan.']);
        }

        if (! hash_equals($target->correctionVersion(), $security['match_version'])) {
            throw ValidationException::withMessages(['match_version' => 'Match sudah berubah sejak formulir dibuka. Muat ulang formulir, periksa data terbaru, lalu ulangi koreksi.']);
        }

        $validated = $this->validatedMatch($request, $event, true);
        $before = $target->correctionValues();
        $oldParticipantIds = $target->participantIds();
        $target->fill($this->matchAttributes($event, $validated, $target));
        $after = $target->correctionValues();

        if ($before === $after) {
            throw ValidationException::withMessages(['match' => 'Belum ada perubahan pada match. Koreksi tidak disimpan.']);
        }

        $target->save();
        $target->corrections()->create([
            'fun_game_id' => $event->id,
            'changed_by' => $actor->id,
            'reason' => $security['correction_reason'],
            'before_values' => $before,
            'after_values' => $after,
        ]);

        $target->unsetRelation('team1')->unsetRelation('team2');
        $participantIds = array_unique([...$oldParticipantIds, ...$target->participantIds()]);
        foreach (User::query()->whereIn('id', $participantIds)->orderBy('id')->lockForUpdate()->get() as $participant) {
            $participant->syncAchievementBadges(['giant_kills', 'streak']);
        }
    }

    private function correctionRateLimitKey(Request $request): string
    {
        return 'completed-match-correction:'.$request->user()->id.'|'.$request->ip();
    }

    public function destroyMatch(Request $request, FunGame $funGame, MatchLog $match): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

        DB::transaction(function () use ($request, $funGame, $match): void {
            $event = FunGame::query()->lockForUpdate()->findOrFail($funGame->id);
            $this->ensureMatchesEditable($event);
            abort_unless($request->user()->fresh()->isAdmin(), 403);
            $target = $event->matches()->lockForUpdate()->findOrFail($match->id);
            $winner = $target->winner_id !== null ? User::withTrashed()->lockForUpdate()->findOrFail($target->winner_id) : null;
            $target->delete();
            if ($winner !== null) {
                $this->syncGiantKillerBadge($winner);
            }
        });

        return back()->with('status_success', 'Match berhasil dihapus. Statistik pemain sudah disesuaikan.');
    }

    private function ensureMatchesEditable(FunGame $funGame): void
    {
        if (! $funGame->isOpen()) {
            throw ValidationException::withMessages([
                'fun_game' => 'Event sudah ditutup. Match tidak dapat diubah atau dihapus.',
            ]);
        }
    }

    private function syncGiantKillerBadge(User $player): void
    {
        $player->syncAchievementBadges(['giant_kills']);
    }

    /**
     * Close the event and assign Champion, Runner-up, and 3rd place (Admin).
     */
    public function closeEvent(Request $request, FunGame $funGame): RedirectResponse
    {
        if (! Auth::user()->isAdmin()) {
            abort(403);
        }

        if ($funGame->isDouble()) {
            return $this->closeDoubleEvent($request, $funGame);
        }

        $validator = Validator::make($request->all(), [
            'champion_id' => ['bail', 'required', 'integer', $this->selectablePlayerRule($this->eventParticipantIds($funGame))],
            'runner_up_id' => ['bail', 'nullable', 'integer', $this->selectablePlayerRule($this->eventParticipantIds($funGame))],
            'third_place_id' => ['bail', 'nullable', 'integer', $this->selectablePlayerRule($this->eventParticipantIds($funGame))],
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

        DB::transaction(function () use ($request, $funGame, $validated): void {
            $event = FunGame::query()->lockForUpdate()->findOrFail($funGame->id);
            abort_unless($request->user()->fresh()->isAdmin(), 403);

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

            $champion = User::withTrashed()->lockForUpdate()->findOrFail($validated['champion_id']);
            $championBadge = Badge::where('slug', 'champion')->first();

            if ($championBadge && ! $champion->badges()->where('badge_id', $championBadge->id)->exists()) {
                $champion->badges()->attach($championBadge->id);
            }
            $participantIds = $event->matches()->pluck('player1_id')->merge($event->matches()->pluck('player2_id'))
                ->merge([$validated['champion_id'], $validated['runner_up_id'] ?? null, $validated['third_place_id'] ?? null])->filter()->unique();
            foreach (User::query()->whereIn('id', $participantIds)->orderBy('id')->lockForUpdate()->get() as $participant) {
                $participant->syncAchievementBadges(['champions', 'streak']);
            }
        });

        return redirect()
            ->route('fun-games.show', $funGame)
            ->with('status_success', "Event '{$funGame->title}' resmi ditutup. Juara telah dinobatkan!");
    }

    public function destroy(Request $request, FunGame $funGame): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);
        $request->validate([
            'delete_confirmation' => ['required', 'string', Rule::in(['HAPUS'])],
        ], [
            'delete_confirmation.required' => 'Ketik HAPUS untuk mengonfirmasi penghapusan histori.',
            'delete_confirmation.in' => 'Ketik HAPUS sesuai petunjuk untuk menghapus histori.',
        ]);

        DB::transaction(function () use ($request, $funGame): void {
            $event = FunGame::query()->lockForUpdate()->findOrFail($funGame->id);

            if ($event->isOpen()) {
                throw ValidationException::withMessages([
                    'fun_game' => 'Event masih berjalan. Tutup event sebelum menghapus histori.',
                ]);
            }

            $winnerIds = $event->matches()->lockForUpdate()->pluck('winner_id');
            $podiumWinnerIds = DB::table('fun_game_podium')->where('fun_game_id', $event->id)
                ->where('position', 1)->lockForUpdate()->pluck('user_id');
            $championIds = $podiumWinnerIds->push($event->champion_id)->filter()->unique();
            $participantIds = $event->matches()->with(['team1', 'team2'])->get()->flatMap(fn (MatchLog $match): array => $match->participantIds())
                ->merge($event->podiumPlayers()->pluck('users.id'))
                ->merge([$event->champion_id, $event->runner_up_id, $event->third_place_id])->filter()->unique();
            $players = User::query()->whereIn('id', $participantIds->merge($winnerIds)->merge($championIds)->unique())
                ->orderBy('id')->lockForUpdate()->get();
            abort_unless($request->user()->fresh()->isAdmin(), 403);

            $event->delete();

            foreach ($players as $player) {
                if ($winnerIds->contains($player->id)) {
                    $this->syncGiantKillerBadge($player);
                }

                if ($championIds->contains($player->id)) {
                    $this->syncChampionBadge($player);
                }
                $player->syncAchievementBadges(['streak']);
            }
        });

        return redirect()->route('fun-games.index')
            ->with('status_success', 'Histori Fun Game beserta seluruh match dan podium berhasil dihapus. Statistik serta lencana terkait sudah disesuaikan.');
    }

    private function syncChampionBadge(User $player): void
    {
        $player->syncAchievementBadges(['champions']);
    }

    private function closeDoubleEvent(Request $request, FunGame $funGame): RedirectResponse
    {
        $positions = ['champion_team_id' => 1, 'runner_up_team_id' => 2, 'third_place_team_id' => 3];
        $rules = [];

        foreach ($positions as $field => $position) {
            $rules[$field] = ['bail', $position === 1 ? 'required' : 'nullable', 'integer', Rule::exists('fun_game_teams', 'id')->where('fun_game_id', $funGame->id)];
        }

        $validator = Validator::make($request->all(), $rules);
        $validator->after(function (ValidatorInstance $validator) use ($request, $positions): void {
            $seen = [];

            foreach ($positions as $field => $position) {
                $teamId = $request->input($field);

                if ($teamId === null || $validator->errors()->has($field)) {
                    continue;
                }

                $teamId = (int) $teamId;
                if (isset($seen[$teamId])) {
                    $validator->errors()->add($field, 'Setiap posisi juara harus diisi pasangan yang berbeda.');
                }
                $seen[$teamId] = true;
            }
        });
        $validated = $validator->validate();

        DB::transaction(function () use ($request, $funGame, $validated, $positions): void {
            $event = FunGame::query()->lockForUpdate()->findOrFail($funGame->id);

            if (! $event->isOpen()) {
                throw ValidationException::withMessages(['fun_game' => 'Event sudah ditutup. Hasil juara tidak dapat diubah melalui penutupan event.']);
            }

            abort_unless($request->user()->fresh()->isAdmin(), 403);
            $teams = $event->teams()->whereIn('id', array_filter($validated))->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $podium = [];

            foreach ($positions as $field => $position) {
                if (! isset($validated[$field])) {
                    continue;
                }

                $team = $teams->get((int) $validated[$field]);
                if ($team === null) {
                    throw ValidationException::withMessages([$field => 'Pasangan tidak lagi terdaftar pada Fun Game ini.']);
                }

                foreach ($team->participantIds() as $playerId) {
                    $podium[$playerId] = ['position' => $position];
                }
            }

            $participantIds = $event->matches()->with(['team1', 'team2'])->get()->flatMap(fn (MatchLog $match): array => $match->participantIds())
                ->merge(array_keys($podium))->unique();
            $participants = User::query()->whereIn('id', $participantIds)->orderBy('id')->lockForUpdate()->get();
            $event->update(['status' => FunGameStatus::Completed, 'champion_id' => null, 'runner_up_id' => null, 'third_place_id' => null]);
            $event->podiumPlayers()->sync($podium);

            foreach ($participants as $participant) {
                $participant->syncAchievementBadges(['champions', 'streak']);
            }
        });

        return redirect()->route('fun-games.show', $funGame)->with('status_success', "Event '{$funGame->title}' resmi ditutup. Pasangan juara telah dinobatkan!");
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function matchAttributes(FunGame $event, array $validated, ?MatchLog $existing = null): array
    {
        if ($event->isDouble()) {
            $teams = $event->teams()->whereIn('id', [$validated['team1_id'], $validated['team2_id']])->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            User::withTrashed()->whereIn('id', $teams->flatMap(fn (FunGameTeam $team): array => $team->participantIds())->unique())->orderBy('id')->lockForUpdate()->get();

            foreach (['team1_id', 'team2_id'] as $field) {
                if (! $teams->has($validated[$field])) {
                    throw ValidationException::withMessages([$field => 'Pasangan tidak lagi terdaftar pada Fun Game ini.']);
                }
                $team = $teams->get($validated[$field]);
                if (($team->player1->trashed() || $team->player2->trashed()) && ! in_array($team->id, [$existing?->team1_id, $existing?->team2_id], true)) {
                    throw ValidationException::withMessages([$field => 'Pasangan dengan anggota yang sudah dihapus hanya tersedia pada histori match sebelumnya.']);
                }
            }

            return [
                ...$validated,
                'stage' => $validated['stage'] ?? 'Penyisihan',
                'player1_id' => null,
                'player2_id' => null,
                'winner_id' => null,
                'player1_hc' => null,
                'player2_hc' => null,
                'voor_type' => null,
                'voor_recipient_id' => null,
            ];
        }

        $snapshots = $existing !== null ? [$existing->player1_id => $existing->player1_hc, $existing->player2_id => $existing->player2_hc] : [];
        $players = User::withTrashed()->whereIn('id', [$validated['player1_id'], $validated['player2_id']])->orderBy('id')->lockForUpdate()->get()->keyBy('id');
        foreach (['player1_id', 'player2_id'] as $field) {
            $player = $players->get($validated[$field]);
            if ($player === null || ($player->trashed() && ! array_key_exists($player->id, $snapshots))) {
                throw ValidationException::withMessages([$field => 'Anggota yang sudah dihapus tidak dapat dipilih untuk match baru.']);
            }
        }
        $firstHc = array_key_exists($validated['player1_id'], $snapshots) ? $snapshots[$validated['player1_id']] : $players->get($validated['player1_id'])->handicap;
        $secondHc = array_key_exists($validated['player2_id'], $snapshots) ? $snapshots[$validated['player2_id']] : $players->get($validated['player2_id'])->handicap;
        $voor = MatchVoor::between($firstHc, $secondHc);
        $recipientId = $voor !== null && $voor !== MatchVoor::None
            ? ($firstHc->isLowerThan($secondHc) ? $validated['player1_id'] : $validated['player2_id']) : null;
        if ($existing !== null && count(array_intersect([$existing->player1_id, $existing->player2_id], [$validated['player1_id'], $validated['player2_id']])) === 2) {
            $voor = $existing->voor_type;
            $recipientId = $existing->voor_recipient_id;
        }

        return [
            ...$validated,
            'stage' => $validated['stage'] ?? 'Penyisihan',
            'player1_hc' => $firstHc,
            'player2_hc' => $secondHc,
            'voor_type' => $voor,
            'voor_recipient_id' => $recipientId,
            'team1_id' => null,
            'team2_id' => null,
            'winner_team_id' => null,
        ];
    }

    /**
     * @return array{player1_id: int|string, player2_id: int|string, player1_score: int|string, player2_score: int|string, winner_id: int, stage?: ?string}
     */
    private function validatedMatch(Request $request, FunGame $funGame, bool $requireMatchingScore = false): array
    {
        if ($funGame->isDouble()) {
            return $this->validatedDoubleMatch($request, $funGame);
        }

        $original = $request->route('match');
        $retainedIds = $original instanceof MatchLog ? array_filter([$original->player1_id, $original->player2_id]) : [];
        $validator = Validator::make($request->all(), [
            'player1_id' => ['bail', 'required', 'integer', $this->selectablePlayerRule($retainedIds)],
            'player2_id' => ['bail', 'required', 'integer', $this->selectablePlayerRule($retainedIds)],
            'player1_score' => ['required', 'integer', 'min:0', 'max:50'],
            'player2_score' => ['required', 'integer', 'min:0', 'max:50'],
            'winner_id' => ['bail', 'required', 'integer'],
            'stage' => ['nullable', 'string', 'max:50'],
        ], [
            'player2_id.different' => 'Pemain 1 dan Pemain 2 tidak boleh sama.',
            'winner_id.in' => 'Pemenang harus salah satu dari Pemain 1 atau Pemain 2.',
        ]);

        $validator->after(function (ValidatorInstance $validator) use ($request, $requireMatchingScore): void {
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

            if (! $requireMatchingScore || $validator->errors()->hasAny(['player1_score', 'player2_score', 'winner_id'])) {
                return;
            }

            $firstScore = (int) $request->input('player1_score');
            $secondScore = (int) $request->input('player2_score');
            if ($firstScore === $secondScore) {
                $validator->errors()->add('player2_score', 'Skor akhir tidak boleh seri.');
            } elseif ((int) $request->input('winner_id') !== (int) $request->input($firstScore > $secondScore ? 'player1_id' : 'player2_id')) {
                $validator->errors()->add('winner_id', 'Pemenang harus pemain dengan skor lebih tinggi.');
            }
        });

        $validated = $validator->validate();
        foreach (['player1_id', 'player2_id', 'winner_id'] as $field) {
            $validated[$field] = (int) $validated[$field];
        }

        return $validated;
    }

    /** @param list<int> $retainedIds */
    private function selectablePlayerRule(array $retainedIds = []): Exists
    {
        return Rule::exists('users', 'id')->where(fn (QueryBuilder $query): QueryBuilder => $query->where(
            fn (QueryBuilder $users): QueryBuilder => $users->whereNull('deleted_at')->orWhereIn('id', $retainedIds),
        ));
    }

    /** @return list<int> */
    private function eventParticipantIds(FunGame $event): array
    {
        return $event->matches()->get(['player1_id', 'player2_id'])
            ->flatMap(fn (MatchLog $match): array => [$match->player1_id, $match->player2_id])->filter()->unique()->values()->all();
    }

    /** @return array{team1_id: int, team2_id: int, player1_score: int|string, player2_score: int|string, winner_team_id: int, stage?: ?string} */
    private function validatedDoubleMatch(Request $request, FunGame $funGame): array
    {
        $validator = Validator::make($request->all(), [
            'team1_id' => ['bail', 'required', 'integer', Rule::exists('fun_game_teams', 'id')->where('fun_game_id', $funGame->id)],
            'team2_id' => ['bail', 'required', 'integer', Rule::exists('fun_game_teams', 'id')->where('fun_game_id', $funGame->id)],
            'player1_score' => ['required', 'integer', 'min:0', 'max:50'],
            'player2_score' => ['required', 'integer', 'min:0', 'max:50'],
            'winner_team_id' => ['bail', 'required', 'integer'],
            'stage' => ['nullable', 'string', 'max:50'],
        ]);

        $validator->after(function (ValidatorInstance $validator) use ($request): void {
            if ($validator->errors()->hasAny(['team1_id', 'team2_id'])) {
                return;
            }

            $team1Id = (int) $request->input('team1_id');
            $team2Id = (int) $request->input('team2_id');
            if ($team1Id === $team2Id) {
                $validator->errors()->add('team2_id', 'Tim 1 dan Tim 2 harus berbeda.');
            }

            if (! $validator->errors()->has('winner_team_id') && ! in_array((int) $request->input('winner_team_id'), [$team1Id, $team2Id], true)) {
                $validator->errors()->add('winner_team_id', 'Pemenang harus Tim 1 atau Tim 2.');
            }

            if ($validator->errors()->hasAny(['player1_score', 'player2_score', 'winner_team_id'])) {
                return;
            }

            $firstScore = (int) $request->input('player1_score');
            $secondScore = (int) $request->input('player2_score');
            if ($firstScore === $secondScore) {
                $validator->errors()->add('player2_score', 'Skor akhir tidak boleh seri.');
            } elseif ((int) $request->input('winner_team_id') !== ($firstScore > $secondScore ? $team1Id : $team2Id)) {
                $validator->errors()->add('winner_team_id', 'Pemenang harus pasangan dengan skor lebih tinggi.');
            }
        });

        $validated = $validator->validate();
        foreach (['team1_id', 'team2_id', 'winner_team_id'] as $field) {
            $validated[$field] = (int) $validated[$field];
        }

        return $validated;
    }
}
