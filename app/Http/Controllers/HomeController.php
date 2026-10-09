<?php

namespace App\Http\Controllers;

use App\Models\Badge;
use App\Models\MatchLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * Show player passport / home dashboard.
     */
    public function index(Request $request): View
    {
        $user = Auth::user();

        $stats = [
            'win_rate' => $user->winRatePercentage(),
            'total_matches' => $user->totalMatchesCount(),
            'wins_vs_higher' => $user->winsAgainstHigherHcCount(),
            'fun_games_count' => $user->funGamesParticipatedCount(),
        ];

        // 4 Most recent completed fun games for the trend row
        $recentFunGames = $user->recentFunGamesSummary(4);

        // Recent matches for the user
        $recentMatches = MatchLog::with(['player1', 'player2', 'winner', 'funGame'])
            ->where(function ($query) use ($user): void {
                $query->where('player1_id', $user->id)
                    ->orWhere('player2_id', $user->id);
            })
            ->latest()
            ->take(5)
            ->get();

        // Badges for user
        $badges = $user->badges()->get();
        $allBadges = Badge::all();

        return view('home', [
            'user' => $user,
            'stats' => $stats,
            'recentFunGames' => $recentFunGames,
            'recentMatches' => $recentMatches,
            'badges' => $badges,
            'allBadges' => $allBadges,
        ]);
    }
}
