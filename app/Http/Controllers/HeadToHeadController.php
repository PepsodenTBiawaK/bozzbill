<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

        // Get all active opponents (excluding current user)
        $opponents = User::where('is_active', true)
            ->where('id', '!=', $user->id)
            ->orderBy('name')
            ->get();

        // Determine selected opponent
        $selectedOpponentId = $request->query('opponent_id');
        $selectedOpponent = null;

        if ($selectedOpponentId) {
            $selectedOpponent = $opponents->firstWhere('id', (int) $selectedOpponentId);
        }

        // If no opponent selected, find the opponent with the most matches against user, or the first opponent
        if (! $selectedOpponent && $opponents->isNotEmpty()) {
            $frequentOpponentId = $user->allMatches()
                ->selectRaw('CASE WHEN player1_id = ? THEN player2_id ELSE player1_id END as rival_id, count(*) as total', [$user->id])
                ->groupBy('rival_id')
                ->orderByDesc('total')
                ->value('rival_id');

            $selectedOpponent = $opponents->firstWhere('id', $frequentOpponentId) ?? $opponents->first();
        }

        $h2hData = $selectedOpponent ? $user->headToHeadWith($selectedOpponent) : [
            'opponent' => null,
            'total_matches' => 0,
            'user_wins' => 0,
            'opponent_wins' => 0,
            'user_percentage' => 0,
            'opponent_percentage' => 0,
            'matches' => collect(),
        ];

        return view('head-to-head', [
            'user' => $user,
            'opponents' => $opponents,
            'selectedOpponent' => $selectedOpponent,
            'h2h' => $h2hData,
        ]);
    }
}
