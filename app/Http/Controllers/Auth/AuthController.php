<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    /**
     * Show the login & account activation page.
     */
    public function showLogin(Request $request): View
    {
        $request->validate([
            'tab' => ['nullable', 'string', 'max:20'],
            'phone' => ['nullable', 'string', 'max:32'],
        ]);

        return view('auth.login', [
            'initialTab' => $request->query('tab', 'login'),
            'initialPhone' => $request->query('phone', ''),
        ]);
    }

    /**
     * Handle member/admin login via phone & password.
     */
    public function login(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:32'],
            'password' => ['required', 'string', 'max:255'],
            'remember' => ['nullable', 'boolean'],
        ]);

        $phone = $this->normalizePhone($validated['phone']);

        $user = User::where('phone', $phone)->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'phone' => 'Nomor WhatsApp ini belum terdaftar di Komunitas Bozz.',
            ]);
        }

        if (! $user->is_active) {
            return redirect()
                ->route('login', ['tab' => 'activation', 'phone' => $phone])
                ->with('status_warning', 'Akun Anda belum aktif. Silakan selesaikan aktivasi akun terlebih dahulu.');
        }

        $remember = (bool) ($validated['remember'] ?? false);

        if (! Auth::attempt(['phone' => $phone, 'password' => $validated['password']], $remember)) {
            throw ValidationException::withMessages([
                'password' => 'Password yang Anda masukkan salah.',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('home'));
    }

    /**
     * Check if a phone number exists and is eligible for activation.
     */
    public function checkActivationPhone(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:32'],
            'activation_code' => ['nullable', 'string', 'regex:/^[0-9]{8}$/'],
        ]);

        $phone = $this->normalizePhone($validated['phone']);

        $user = User::where('phone', $phone)->first();

        if (! $user) {
            return response()->json([
                'status' => 'not_found',
                'message' => 'Nomor WhatsApp belum terdaftar. Silakan hubungi pengurus Bozz Billiard untuk didaftarkan.',
            ], 404);
        }

        if ($user->is_active && ! empty($user->password)) {
            return response()->json([
                'status' => 'already_active',
                'phone' => $phone,
                'message' => 'Akun Anda sudah aktif. Silakan masuk, Bozz, dengan password Anda.',
            ]);
        }

        if (! $user->hasValidActivationCode($validated['activation_code'] ?? '')) {
            throw ValidationException::withMessages([
                'activation_code' => 'Kode aktivasi tidak valid atau sudah kedaluwarsa. Minta kode baru kepada pengurus.',
            ]);
        }

        return response()->json([
            'status' => 'ready',
            'user' => [
                'name' => $user->name,
                'phone' => $user->phone,
                'handicap' => $user->handicap->value,
                'handicap_label' => $user->handicap->label(),
            ],
            'message' => "Halo {$user->name}! Akun Anda siap diaktivasi dengan Handicap awal {$user->handicap->value}.",
        ]);
    }

    /**
     * Complete member self-activation by setting password.
     */
    public function activate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:32'],
            'activation_code' => ['nullable', 'string', 'regex:/^[0-9]{8}$/'],
            'password' => ['required', 'string', 'min:6', 'max:255', 'confirmed'],
            'remember' => ['nullable', 'boolean'],
        ], [
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'password.min' => 'Password minimal harus 6 karakter.',
        ]);

        $phone = $this->normalizePhone($validated['phone']);

        $result = DB::transaction(function () use ($phone, $validated): User|RedirectResponse {
            $user = User::where('phone', $phone)->lockForUpdate()->first();

            if (! $user) {
                return redirect()
                    ->route('login', ['tab' => 'activation', 'phone' => $phone])
                    ->withErrors(['phone' => 'Nomor WhatsApp tidak ditemukan.']);
            }

            if ($user->is_active && ! empty($user->password)) {
                if (! Hash::check($validated['password'], $user->password)) {
                    return redirect()
                        ->route('login', ['tab' => 'login', 'phone' => $phone])
                        ->with('status_info', 'Akun Anda sudah aktif. Silakan masuk, Bozz, dengan password akun Anda.')
                        ->withErrors(['password' => 'Password yang Anda masukkan tidak cocok dengan password akun Anda.']);
                }

                return $user;
            }

            if (! $user->hasValidActivationCode($validated['activation_code'] ?? '')) {
                throw ValidationException::withMessages([
                    'activation_code' => 'Kode aktivasi tidak valid atau sudah kedaluwarsa. Minta kode baru kepada pengurus.',
                ]);
            }

            $user->forceFill([
                'password' => Hash::make($validated['password']),
                'is_active' => true,
                'activation_code_hash' => null,
                'activation_code_expires_at' => null,
            ])->save();

            return $user;
        });

        if ($result instanceof RedirectResponse) {
            return $result;
        }

        $user = $result;

        Auth::login($user, (bool) ($validated['remember'] ?? false));
        $request->session()->regenerate();

        return redirect()
            ->route('home')
            ->with('status_success', "Selamat datang, {$user->name}! Akun Anda sudah aktif.");
    }

    /**
     * Log out the authenticated user.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /**
     * Normalize Indonesian phone numbers (e.g. 0812..., +62812..., 62812... -> 0812...).
     */
    private function normalizePhone(string $phone): string
    {
        $cleaned = preg_replace('/[^\d]/', '', $phone);

        if (str_starts_with($cleaned, '62')) {
            $cleaned = '0'.substr($cleaned, 2);
        }

        return $cleaned;
    }
}
