<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SsoController extends Controller
{
    public function showLogin(Request $request)
    {
        if (Auth::check()) {
            return redirect()->intended('/');
        }

        $configuredClientId = config('services.sso.client_id');
        $requestedClientId = $request->query('client_id');
        $shouldAutoRedirect = $request->boolean('login_sso')
            && $configuredClientId
            && $requestedClientId === $configuredClientId;

        $ssoError = $request->query('sso_error');

        if ($request->boolean('login_sso') && $requestedClientId && $requestedClientId !== $configuredClientId) {
            $ssoError = 'Client ID SSO tidak cocok dengan aplikasi ini.';
        }

        return view('auth.login', [
            'ssoError' => $ssoError,
            'clientId' => $configuredClientId,
            'shouldAutoRedirect' => $shouldAutoRedirect,
            'devBypassEnabled' => $this->isDevBypassEnabled(),
        ]);
    }

    public function devBypassLogin(Request $request)
    {
        abort_unless($this->isDevBypassEnabled(), 404);

        $username = (string) config('services.sso.dev_bypass_username');
        $name = (string) config('services.sso.dev_bypass_name');
        $role = (string) config('services.sso.dev_bypass_role');

        $user = User::query()->firstOrCreate(
            ['username' => $username],
            [
                'name' => $name,
                'email' => null,
                'role' => $role,
                'sso_id' => null,
                'password' => null,
            ],
        );

        $user->forceFill([
            'name' => $name,
            'role' => $role,
        ])->save();

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->forget([
            'is_sso_authenticated',
            'sso_access_token',
            'sso_refresh_token',
            'sso_token_expires_at',
            'sso_user',
        ]);

        return redirect()->intended('/');
    }

    public function redirectToSso(Request $request)
    {
        if (Auth::check()) {
            return redirect()->intended('/');
        }

        $configuredClientId = config('services.sso.client_id');
        $requestedClientId = $request->query('client_id');

        if ($requestedClientId && $requestedClientId !== $configuredClientId) {
            return redirect()->route('login', [
                'sso_error' => 'Client ID SSO tidak cocok dengan aplikasi ini.',
            ]);
        }

        if (! $this->isConfigured()) {
            abort(503, 'SSO belum dikonfigurasi.');
        }

        $query = http_build_query([
            'client_id' => $configuredClientId,
            'redirect_uri' => config('services.sso.callback_url'),
            'response_type' => 'code',
            'state' => $this->generateSignedState(),
        ]);

        $frontendUrl = config('services.sso.frontend_url');
        $authorizeUrl = $frontendUrl
            ? "{$frontendUrl}/login?{$query}"
            : config('services.sso.base_url')."/api/v1/oauth/authorize?{$query}";

        return redirect()->away($authorizeUrl);
    }

    public function handleCallback(Request $request)
    {
        Log::info('SSO callback received', [
            'has_code' => $request->filled('code'),
            'has_state' => $request->filled('state'),
            'error' => $request->query('error'),
            'callback_url' => $request->fullUrl(),
        ]);

        if ($request->filled('error')) {
            return redirect()->route('login', [
                'sso_error' => $request->query('error_description', $request->query('error')),
            ]);
        }

        if (! $this->isConfigured()) {
            Log::warning('SSO callback aborted because configuration is incomplete', [
                'base_url' => filled(config('services.sso.base_url')),
                'client_id' => filled(config('services.sso.client_id')),
                'client_secret' => filled(config('services.sso.client_secret')),
                'callback_url' => filled(config('services.sso.callback_url')),
            ]);

            abort(503, 'SSO belum dikonfigurasi.');
        }

        if (! $this->isValidState($request->query('state'))) {
            Log::warning('SSO callback rejected because state is invalid', [
                'state' => $request->query('state'),
            ]);

            return redirect()->route('login', [
                'sso_error' => 'State parameter tidak valid.',
            ]);
        }

        $code = $request->query('code');

        if (! $code) {
            return redirect()->route('login', [
                'sso_error' => 'Authorization code tidak ditemukan.',
            ]);
        }

        try {
            Log::info('SSO token exchange started', [
                'base_url' => config('services.sso.base_url'),
                'redirect_uri' => config('services.sso.callback_url'),
            ]);

            $tokenResponse = Http::acceptJson()
                ->connectTimeout(5)
                ->timeout(15)
                ->post(config('services.sso.base_url').'/api/v1/oauth/token', [
                    'grant_type' => 'authorization_code',
                    'code' => $code,
                    'redirect_uri' => config('services.sso.callback_url'),
                    'client_id' => config('services.sso.client_id'),
                    'client_secret' => config('services.sso.client_secret'),
                ])
                ->throw();

            $tokenData = $tokenResponse->json('data', []);

            Log::info('SSO token exchange completed', [
                'has_access_token' => isset($tokenData['access_token']),
                'has_refresh_token' => isset($tokenData['refresh_token']),
                'expires_in' => $tokenData['expires_in'] ?? null,
            ]);

            if (! isset($tokenData['access_token'], $tokenData['refresh_token'], $tokenData['expires_in'])) {
                return redirect()->route('login', [
                    'sso_error' => 'Respons token SSO tidak lengkap.',
                ]);
            }

            Log::info('SSO userinfo request started');

            $userInfoResponse = Http::acceptJson()
                ->connectTimeout(5)
                ->timeout(15)
                ->withToken($tokenData['access_token'])
                ->get(config('services.sso.base_url').'/api/v1/oauth/userinfo')
                ->throw();

            $userInfo = $userInfoResponse->json('data', []);

            Log::info('SSO userinfo request completed', [
                'sso_id' => $userInfo['id'] ?? null,
                'username' => $userInfo['username'] ?? null,
            ]);

            if (! isset($userInfo['id'], $userInfo['username'])) {
                return redirect()->route('login', [
                    'sso_error' => 'Respons userinfo SSO tidak lengkap.',
                ]);
            }

            $user = $this->findOrCreateUser($userInfo);

            Auth::login($user);
            $request->session()->regenerate();
            $request->session()->put([
                'is_sso_authenticated' => true,
                'sso_access_token' => $tokenData['access_token'],
                'sso_refresh_token' => $tokenData['refresh_token'],
                'sso_token_expires_at' => now()->addSeconds((int) $tokenData['expires_in'])->toIso8601String(),
                'sso_user' => [
                    'id' => $userInfo['id'],
                    'username' => $userInfo['username'],
                    'name' => $userInfo['name'] ?? $user->name,
                    'role' => $userInfo['role'] ?? $user->role,
                ],
            ]);

            Log::info('SSO login completed', [
                'local_user_id' => $user->id,
                'username' => $user->username,
            ]);

            return redirect()->intended('/');
        } catch (RequestException $exception) {
            $message = $exception->response?->json('message') ?: 'Autentikasi SSO gagal.';

            Log::error('SSO callback failed during HTTP request', [
                'message' => $message,
                'status' => $exception->response?->status(),
                'response' => $exception->response?->json(),
            ]);

            return redirect()->route('login', [
                'sso_error' => $message,
            ]);
        } catch (\Throwable $exception) {
            Log::error('SSO callback failed unexpectedly', [
                'message' => $exception->getMessage(),
            ]);

            return redirect()->route('login', [
                'sso_error' => 'Terjadi kesalahan saat memproses login SSO.',
            ]);
        }
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function findOrCreateUser(array $userInfo): User
    {
        $name = $userInfo['name'] ?? $userInfo['username'];
        $role = $userInfo['role'] ?? 'user';

        $user = User::query()->where('sso_id', $userInfo['id'])->first();

        if ($user) {
            $user->forceFill([
                'username' => $userInfo['username'],
                'name' => $name,
                'role' => $user->role ?: 'user',
            ])->save();

            return $user;
        }

        $user = User::query()->where('username', $userInfo['username'])->first();

        if ($user) {
            $user->forceFill([
                'sso_id' => $userInfo['id'],
                'name' => $name,
                'role' => $user->role ?: 'user',
            ])->save();

            return $user;
        }

        return User::query()->create([
            'name' => $name,
            'username' => $userInfo['username'],
            'email' => null,
            'role' => 'user',
            'sso_id' => $userInfo['id'],
            'password' => null,
        ]);
    }

    private function generateSignedState(): string
    {
        $timestamp = (string) now()->getTimestampMs();

        return $timestamp.'.'.$this->signStateTimestamp($timestamp);
    }

    private function isValidState(?string $state): bool
    {
        if (! $state || ! str_contains($state, '.')) {
            return false;
        }

        [$timestamp, $signature] = explode('.', $state, 2);

        if (! ctype_digit($timestamp) || ! $signature) {
            return false;
        }

        $expected = $this->signStateTimestamp($timestamp);

        if (! hash_equals($expected, $signature)) {
            return false;
        }

        $maxAgeMs = 10 * 60 * 1000;

        return (now()->getTimestampMs() - (int) $timestamp) <= $maxAgeMs;
    }

    private function signStateTimestamp(string $timestamp): string
    {
        return hash_hmac('sha256', $timestamp, $this->stateSecret());
    }

    private function stateSecret(): string
    {
        $appKey = (string) config('app.key', '');

        return Str::startsWith($appKey, 'base64:')
            ? base64_decode(Str::after($appKey, 'base64:'), true) ?: $appKey
            : $appKey;
    }

    private function isConfigured(): bool
    {
        return filled(config('services.sso.base_url'))
            && filled(config('services.sso.client_id'))
            && filled(config('services.sso.client_secret'))
            && filled(config('services.sso.callback_url'));
    }

    private function isDevBypassEnabled(): bool
    {
        return app()->environment('local')
            && (bool) config('services.sso.dev_bypass_enabled');
    }
}
