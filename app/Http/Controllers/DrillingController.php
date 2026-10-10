<?php

namespace App\Http\Controllers;

use App\Models\PlayerDrill;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DrillingController extends Controller
{
    /**
     * Display drilling journal dashboard and past practice sessions.
     */
    public function index(Request $request): View
    {
        /** @var User $user */
        $user = Auth::user();

        $overallAccuracy = $user->drillsOverallAccuracy();
        $totalDrills = $user->playerDrills()->count();
        $thisMonthCount = $user->drillsThisMonthCount();

        $recentDrills = $user->playerDrills()
            ->latest('practice_date')
            ->latest('id')
            ->paginate(10);

        return view('drilling.index', [
            'user' => $user,
            'overallAccuracy' => $overallAccuracy,
            'totalDrills' => $totalDrills,
            'thisMonthCount' => $thisMonthCount,
            'recentDrills' => $recentDrills,
        ]);
    }

    /**
     * Store a newly recorded solo drilling practice session.
     */
    public function store(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $validated = $request->validate([
            'drill_name' => ['required', 'string', 'max:150'],
            'success_count' => ['required', 'integer', 'min:0', 'max:500'],
            'fail_count' => ['required', 'integer', 'min:0', 'max:500'],
            'practice_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'drill_name.required' => 'Nama drill latihan wajib diisi.',
            'success_count.required' => 'Jumlah berhasil wajib ditentukan.',
            'fail_count.required' => 'Jumlah gagal wajib ditentukan.',
        ]);

        if (($validated['success_count'] + $validated['fail_count']) === 0) {
            return back()
                ->withInput()
                ->withErrors(['success_count' => 'Total percobaan latihan minimal 1 kali (Berhasil atau Gagal).']);
        }

        $practiceDate = ! empty($validated['practice_date'])
            ? $validated['practice_date']
            : now()->toDateString();

        $drill = PlayerDrill::create([
            'user_id' => $user->id,
            'drill_name' => $validated['drill_name'],
            'success_count' => $validated['success_count'],
            'fail_count' => $validated['fail_count'],
            'practice_date' => $practiceDate,
            'notes' => $validated['notes'] ?? null,
        ]);

        $user->syncAchievementBadges(['accuracy', 'drill_sessions']);

        return redirect()
            ->route('drilling')
            ->with('status_success', "Sesi latihan '{$drill->drill_name}' ({$drill->accuracy_percentage}% Akurasi) berhasil dicatat!");
    }
}
