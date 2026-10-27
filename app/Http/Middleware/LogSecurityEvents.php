<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class LogSecurityEvents
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $request->attributes->set('security_request_id', (string) Str::uuid());
        $request->attributes->set('security_actor_id', $request->user()?->id);

        $response = $next($request);
        $response->headers->set('X-Request-ID', $request->attributes->get('security_request_id'));
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'same-origin');
        $response->headers->set('Cache-Control', 'private, no-store');

        return $response;
    }

    public function terminate(Request $request, Response $response): void
    {
        $status = $response->getStatusCode();
        $isDenied = in_array($status, [401, 403, 419, 429], true);
        $isServerError = $status >= 500;
        $isWrite = ! $request->isMethodSafe() && $request->route()?->getName() !== null;

        if (! $isDenied && ! $isServerError && ! $isWrite) {
            return;
        }

        $hasErrors = $request->hasSession() && $request->session()->has('errors');
        $event = match (true) {
            $isServerError => 'server.error',
            $status === 429 => 'rate_limit.exceeded',
            $isDenied => 'access.denied',
            $status >= 400 || $hasErrors => 'action.rejected',
            default => 'action.completed',
        };
        $subjects = [];
        foreach (['user', 'funGame', 'match', 'team'] as $parameter) {
            $value = $request->route($parameter);
            if ($value instanceof Model) {
                $subjects[$parameter] = $value->getKey();
            } elseif (is_string($value) && ctype_digit($value)) {
                $subjects[$parameter] = (int) $value;
            }
        }

        Log::channel('security')->log($isServerError ? 'error' : ($event === 'action.completed' ? 'info' : 'warning'), $event, [
            'request_id' => $request->attributes->get('security_request_id'),
            'route' => $request->route()?->getName(),
            'method' => $request->method(),
            'status' => $status,
            'actor_id' => $request->attributes->get('security_actor_id') ?? $request->user()?->id,
            'subjects' => $subjects,
            'ip_hash' => hash_hmac('sha256', (string) $request->ip(), (string) config('app.key')),
        ]);
    }
}
