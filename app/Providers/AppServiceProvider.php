<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('login', fn (Request $request): array => [
            Limit::perMinute(30)->by('login-ip:'.$request->ip()),
            Limit::perMinute(5)->by('login-account:'.$this->accountFingerprint($request).'|'.$request->ip()),
        ]);

        RateLimiter::for('activation', fn (Request $request): array => [
            Limit::perMinute(30)->by('activation-ip:'.$request->ip()),
            Limit::perMinute(5)->by('activation-pair:'.$this->accountFingerprint($request).'|'.$request->ip()),
            Limit::perHour(10)->by('activation-account:'.$this->accountFingerprint($request)),
        ]);

        RateLimiter::for('application', fn (Request $request): Limit => Limit::perMinute(
            $request->isMethodSafe() ? 120 : 30,
        )->by(($request->isMethodSafe() ? 'read:' : 'write:').$request->user()->id));

        RateLimiter::for('account-security', fn (Request $request): Limit => Limit::perMinute(10)
            ->by('account-security:'.$request->user()->id));
        RateLimiter::for('roster-deletion', fn (Request $request): Limit => Limit::perMinute(5)
            ->by('roster-deletion:'.$request->user()->id));
    }

    private function accountFingerprint(Request $request): string
    {
        $phone = $request->input('phone');
        $normalizedPhone = is_string($phone) && strlen($phone) <= 64 ? User::normalizePhone($phone) : 'invalid';

        return hash_hmac('sha256', $normalizedPhone, (string) config('app.key'));
    }
}
