<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

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
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_avatar' => ['nullable', 'boolean'],
        ], [
            'avatar.image' => 'Foto profil harus berupa gambar.',
            'avatar.mimes' => 'Foto profil harus berformat JPG, PNG, atau WebP.',
            'avatar.max' => 'Ukuran foto profil maksimal 2 MB.',
            'remove_avatar.boolean' => 'Pilihan hapus foto profil tidak valid.',
        ]);

        if ($request->hasFile('avatar') && $request->boolean('remove_avatar')) {
            throw ValidationException::withMessages([
                'avatar' => 'Pilih foto baru atau hapus foto saat ini, salah satu saja.',
            ]);
        }

        $oldAvatarPath = $user->avatar;
        $newAvatarPath = null;
        $attributes = [
            'name' => $validated['name'],
            'avatar' => $oldAvatarPath,
        ];

        if ($request->hasFile('avatar')) {
            $newAvatarPath = $request->file('avatar')->store('avatars', 'public');

            if ($newAvatarPath === false) {
                throw ValidationException::withMessages([
                    'avatar' => 'Foto profil gagal disimpan. Silakan coba lagi.',
                ]);
            }

            $attributes['avatar'] = $newAvatarPath;
        } elseif ($request->boolean('remove_avatar')) {
            $attributes['avatar'] = null;
        }

        try {
            DB::transaction(function () use ($user, $attributes): void {
                if (! $user->update($attributes)) {
                    throw new RuntimeException('Profil gagal disimpan.');
                }
            });
        } catch (Throwable $exception) {
            if ($newAvatarPath !== null) {
                Storage::disk('public')->delete($newAvatarPath);
            }

            throw $exception;
        }

        if ($oldAvatarPath && $oldAvatarPath !== $attributes['avatar']) {
            Storage::disk('public')->delete($oldAvatarPath);
        }

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
