<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Show player profile and Handicap History Timeline.
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        $hcHistories = $user->hcHistories()->with('changer')->get();
        $badges = $user->badges()->get();

        return view('profile.index', [
            'user' => $user,
            'hcHistories' => $hcHistories,
            'badges' => $badges,
        ]);
    }

    /**
     * Update user profile information.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
        ]);

        $user->update([
            'name' => $validated['name'],
        ]);

        return redirect()->route('profile')->with('status_success', 'Profil Anda berhasil diperbarui.');
    }

    /**
     * Update user password.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(6)],
        ], [
            'current_password.current_password' => 'Password saat ini salah.',
            'password.confirmed' => 'Konfirmasi password baru tidak cocok.',
            'password.min' => 'Password baru minimal harus 6 karakter.',
        ]);

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()->route('profile')->with('status_success', 'Password Anda berhasil diperbarui.');
    }
}
