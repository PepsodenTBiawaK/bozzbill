<?php

namespace App\Http\Controllers;

use App\Enums\EvaluationRecommendation;
use App\Enums\FunGameStatus;
use App\Enums\Handicap;
use App\Models\FunGame;
use App\Models\MatchLog;
use App\Models\PlayerDrill;
use App\Models\User;
use App\WinRateRanking;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Show the community's operational summary for administrators.
     */
    public function index(Request $request): View
    {
        abort_unless($request->user()->isAdmin(), 403);

        $members = User::query()->with('hcHistories')->get(['id', 'handicap', 'is_active']);
        $activeMembers = $members->where('is_active', true);

        $activeEvent = FunGame::where('status', FunGameStatus::Open)
            ->withCount('matches')
            ->latest('event_date')
            ->latest('id')
            ->first();

        $evaluationMatches = WinRateRanking::completedSingles()->with('funGame')->get();
        $matchesAsPlayerOne = $evaluationMatches->groupBy('player1_id');
        $matchesAsPlayerTwo = $evaluationMatches->groupBy('player2_id');
        $recommendationCounts = ['promote' => 0, 'demote' => 0, 'watch' => 0, 'stay' => 0];
        $handicapDistribution = array_fill_keys(array_column(Handicap::cases(), 'value'), 0);

        foreach ($members as $member) {
            $memberMatches = $matchesAsPlayerOne->get($member->id, collect())
                ->concat($matchesAsPlayerTwo->get($member->id, collect()));
            $evaluation = $member->evaluateHandicap($memberMatches);
            $recommendationKey = match ($evaluation['recommendation']) {
                EvaluationRecommendation::Promote => 'promote',
                EvaluationRecommendation::Demote => 'demote',
                EvaluationRecommendation::Watch => 'watch',
                EvaluationRecommendation::Stay => 'stay',
            };

            $recommendationCounts[$recommendationKey]++;
            $handicapDistribution[$member->handicap->value]++;
        }

        return view('dashboard', [
            'stats' => [
                'active_members' => $activeMembers->count(),
                'inactive_members' => $members->where('is_active', false)->count(),
                'total_matches' => MatchLog::count(),
                'completed_events' => FunGame::where('status', FunGameStatus::Completed)->count(),
                'drills_this_month' => PlayerDrill::whereBetween('practice_date', [
                    now()->startOfMonth()->toDateString(),
                    now()->endOfMonth()->toDateString(),
                ])->count(),
            ],
            'activeEvent' => $activeEvent,
            'recentMatches' => MatchLog::with(['player1', 'player2', 'winner', 'funGame', 'recorder', 'team1.player1', 'team1.player2', 'team2.player1', 'team2.player2'])
                ->latest()
                ->latest('id')
                ->take(5)
                ->get(),
            'recommendationCounts' => $recommendationCounts,
            'handicapDistribution' => $handicapDistribution,
        ]);
    }
}
