<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\DrillingController;
use App\Http\Controllers\FunGameController;
use App\Http\Controllers\HandicapEvaluationController;
use App\Http\Controllers\HeadToHeadController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RosterController;
use Illuminate\Support\Facades\Route;

// Guest Routes
Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
    Route::post('/check-activation', [AuthController::class, 'checkActivationPhone'])->name('activation.check');
    Route::post('/activate', [AuthController::class, 'activate'])->name('activate.post');
});

// Authenticated Routes
Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Home / Paspor Pemain
    Route::get('/', [HomeController::class, 'index'])->name('home');

    // Profil Pemain & Riwayat HC
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    // Head to Head
    Route::get('/head-to-head', [HeadToHeadController::class, 'index'])->name('head-to-head');

    // Jurnal Drilling
    Route::get('/drilling', [DrillingController::class, 'index'])->name('drilling');
    Route::post('/drilling', [DrillingController::class, 'store'])->name('drilling.store');

    // Evaluasi Handicap (Admin)
    Route::get('/evaluating-hc', [HandicapEvaluationController::class, 'index'])->name('evaluating-hc.index');
    Route::post('/evaluating-hc/{user}/approve', [HandicapEvaluationController::class, 'approve'])->name('evaluating-hc.approve');

    // Komunitas Roster & Leaderboard
    Route::get('/roster', [RosterController::class, 'index'])->name('roster');
    Route::post('/members', [RosterController::class, 'storeMember'])->name('members.store');

    // Modul Fun Game & Match Logger
    Route::get('/fun-games', [FunGameController::class, 'index'])->name('fun-games.index');
    Route::get('/fun-games/quick-logger', [FunGameController::class, 'quickLogger'])->name('fun-games.quick-logger');
    Route::post('/fun-games', [FunGameController::class, 'store'])->name('fun-games.store');
    Route::get('/fun-games/{funGame}', [FunGameController::class, 'show'])->name('fun-games.show');
    Route::post('/fun-games/{funGame}/matches', [FunGameController::class, 'storeMatch'])->name('fun-games.matches.store');
    Route::post('/fun-games/{funGame}/close', [FunGameController::class, 'closeEvent'])->name('fun-games.close');
});
