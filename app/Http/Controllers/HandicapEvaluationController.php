<?php

namespace App\Http\Controllers;

use App\Enums\EvaluationRecommendation;
use App\Enums\Handicap;
use App\Enums\UserRole;
use App\Models\HcHistory;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class HandicapEvaluationController extends Controller
{
    /**
     * Display Handicap Evaluation dashboard for Admin/Pengurus.
     */
    public function index(Request $request): View
    {
        if (! Auth::user()?->isAdmin()) {
            abort(403, 'Akses terbatas untuk Pengurus BozzBill.');
        }

        $filter = $request->query('filter', 'all');

        $players = User::where('role', UserRole::Member)
            ->orderBy('name')
            ->get();

        $evaluations = [];
        $counts = [
            'all' => 0,
            'promote' => 0,
            'watch' => 0,
            'stay' => 0,
            'demote' => 0,
        ];

        foreach ($players as $player) {
            $eval = $player->evaluateHandicap();
            $evaluations[] = [
                'user' => $player,
                'data' => $eval,
            ];

            $counts['all']++;
            match ($eval['recommendation']) {
                EvaluationRecommendation::Promote => $counts['promote']++,
                EvaluationRecommendation::Watch => $counts['watch']++,
                EvaluationRecommendation::Stay => $counts['stay']++,
                EvaluationRecommendation::Demote => $counts['demote']++,
            };
        }

        // Apply filter
        if ($filter !== 'all') {
            $targetRec = match ($filter) {
                'promote' => EvaluationRecommendation::Promote,
                'watch' => EvaluationRecommendation::Watch,
                'stay' => EvaluationRecommendation::Stay,
                'demote' => EvaluationRecommendation::Demote,
                default => null,
            };

            if ($targetRec) {
                $evaluations = array_filter($evaluations, fn ($item) => $item['data']['recommendation'] === $targetRec);
            }
        }

        return view('admin.evaluations.index', [
            'evaluations' => $evaluations,
            'counts' => $counts,
            'activeFilter' => $filter,
        ]);
    }

    /**
     * Approve and execute a handicap evaluation decision.
     */
    public function approve(Request $request, User $user): RedirectResponse
    {
        if (! Auth::user()?->isAdmin()) {
            abort(403, 'Akses terbatas untuk Pengurus BozzBill.');
        }

        $validated = $request->validate([
            'decision' => ['required', Rule::in(['promote', 'demote', 'stay', 'watch'])],
            'new_hc' => ['required', Rule::enum(Handicap::class)],
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $oldHc = $user->handicap;
        $newHc = Handicap::from($validated['new_hc']);

        if ($validated['decision'] === 'promote' || $validated['decision'] === 'demote') {
            // Record audit trail
            HcHistory::create([
                'user_id' => $user->id,
                'old_hc' => $oldHc,
                'new_hc' => $newHc,
                'reason' => $validated['reason'],
                'changed_by' => Auth::id(),
                'approved_at' => now(),
            ]);

            // Update user handicap
            $user->update([
                'handicap' => $newHc,
            ]);

            $message = "Keputusan resmi: Handicap {$user->name} berhasil diperbarui dari {$oldHc->value} menjadi {$newHc->value}!";
        } else {
            $message = "Keputusan dicatat: {$user->name} tetap pada level HC {$user->handicap->value} ({$validated['reason']}).";
        }

        return redirect()
            ->route('evaluating-hc.index')
            ->with('status_success', $message);
    }
}
