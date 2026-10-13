<?php

namespace App\Http\Controllers;

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
        $recentMatches = $user->allMatches()
            ->with(['player1', 'player2', 'winner', 'funGame', 'team1.player1', 'team1.player2', 'team2.player1', 'team2.player2', 'winnerTeam'])
            ->latest()
            ->take(5)
            ->get();

        // Badges for user
        $badges = $user->badges()->get();
        $achievements = $user->achievementProgress();

        return view('home', [
            'user' => $user,
            'stats' => $stats,
            'recentFunGames' => $recentFunGames,
            'recentMatches' => $recentMatches,
            'badges' => $badges,
            'achievements' => $achievements->sortByDesc('unlocked')->take(3)->values(),
        ]);
    }
}
