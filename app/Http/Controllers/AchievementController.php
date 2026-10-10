<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class AchievementController extends Controller
{
    public function index(Request $request): View
    {
        $achievements = $request->user()->achievementProgress();

        return view('achievements', ['achievements' => $achievements,
            'unlockedCount' => $achievements->where('unlocked', true)->count()]);
    }
}
