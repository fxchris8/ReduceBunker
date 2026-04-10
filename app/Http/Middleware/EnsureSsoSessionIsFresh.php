<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

class EnsureSsoSessionIsFresh
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->session()->get('is_sso_authenticated')) {
            return $next($request);
        }

        $expiresAt = $request->session()->get('sso_token_expires_at');

        if (! $expiresAt) {
            return $this->forceReauthentication($request, 'Sesi SSO tidak lengkap. Silakan login kembali.');
        }

        if (Carbon::parse($expiresAt)->subMinutes(2)->isFuture()) {
            return $next($request);
        }

        $refreshToken = $request->session()->get('sso_refresh_token');
        $baseUrl = config('services.sso.base_url');
        $clientId = config('services.sso.client_id');
        $clientSecret = config('services.sso.client_secret');

        if (! $refreshToken || ! $baseUrl || ! $clientId || ! $clientSecret) {
            return $this->forceReauthentication($request, 'Konfigurasi refresh token SSO tidak lengkap.');
        }

        try {
            $response = Http::acceptJson()
                ->timeout(15)
                ->post("{$baseUrl}/api/v1/oauth/token", [
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $refreshToken,
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                ])
                ->throw();

            $payload = $response->json('data', []);

            if (! isset($payload['access_token'], $payload['refresh_token'], $payload['expires_in'])) {
                return $this->forceReauthentication($request, 'Refresh token SSO gagal diproses.');
            }

            $request->session()->put([
                'sso_access_token' => $payload['access_token'],
                'sso_refresh_token' => $payload['refresh_token'],
                'sso_token_expires_at' => now()->addSeconds((int) $payload['expires_in'])->toIso8601String(),
            ]);
        } catch (RequestException) {
            return $this->forceReauthentication($request, 'Sesi SSO berakhir. Silakan login kembali.');
        }

        return $next($request);
    }

    private function forceReauthentication(Request $request, string $message): Response
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login', ['sso_error' => $message]);
    }
}
