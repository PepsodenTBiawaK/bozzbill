<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class HeadToHeadController extends Controller
{
    /**
     * Display Head To Head comparison page.
     */
    public function index(Request $request): View
    {
        /** @var User $user */
        $user = Auth::user();

        $players = User::query()
            ->orderBy('name')
            ->get();

        $defaultPlayer1Id = $players->contains('id', $user->id)
            ? $user->id
            : $players->first()?->id;

        $validator = Validator::make([
            'player1_id' => $request->query('player1_id', $defaultPlayer1Id),
            'opponent_id' => $request->query('opponent_id'),
        ], [
            'player1_id' => ['nullable', 'integer', Rule::in($players->modelKeys())],
            'opponent_id' => ['nullable', 'integer', Rule::in($players->modelKeys())],
        ], [
            'player1_id.integer' => 'Pilih pemain pertama yang valid.',
            'player1_id.in' => 'Pemain pertama tidak ditemukan.',
            'opponent_id.integer' => 'Pilih pemain kedua yang valid.',
            'opponent_id.in' => 'Pemain kedua tidak ditemukan.',
        ]);

        if ($validator->fails()) {
            throw (new ValidationException($validator))->redirectTo(route('head-to-head'));
        }

        $validated = $validator->validated();

        $selectedPlayer1 = isset($validated['player1_id'])
            ? $players->firstWhere('id', (int) $validated['player1_id'])
            : null;
        $selectedOpponent = isset($validated['opponent_id'])
            ? $players->firstWhere('id', (int) $validated['opponent_id'])
            : null;

        if ($selectedPlayer1 && $selectedOpponent && $selectedPlayer1->is($selectedOpponent)) {
            throw ValidationException::withMessages([
                'opponent_id' => 'Pemain pertama dan pemain kedua harus berbeda.',
            ])->redirectTo(route('head-to-head'));
        }

        $opponents = $players->reject(fn (User $player): bool => $player->id === $selectedPlayer1?->id);

        if ($selectedPlayer1 && ! $request->query->has('opponent_id') && $opponents->isNotEmpty()) {
            $frequentOpponentId = $selectedPlayer1->allMatches()
                ->selectRaw('CASE WHEN player1_id = ? THEN player2_id ELSE player1_id END as rival_id, count(*) as total', [$selectedPlayer1->id])
                ->groupBy('rival_id')
                ->orderByDesc('total')
                ->value('rival_id');

            $selectedOpponent = $opponents->firstWhere('id', $frequentOpponentId) ?? $opponents->first();
        }

        $h2hData = $selectedPlayer1 && $selectedOpponent ? $selectedPlayer1->headToHeadWith($selectedOpponent) : [
            'opponent' => null,
            'total_matches' => 0,
            'user_wins' => 0,
            'opponent_wins' => 0,
            'user_percentage' => 0,
            'opponent_percentage' => 0,
            'matches' => collect(),
        ];

        return view('head-to-head', [
            'players' => $players,
            'selectedPlayer1' => $selectedPlayer1,
            'selectedOpponent' => $selectedOpponent,
            'h2h' => $h2hData,
        ]);
    }
}
