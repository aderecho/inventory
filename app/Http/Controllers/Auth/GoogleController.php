<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\DashboardAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;

class GoogleController extends Controller
{
    private const SESSION_KEY = 'google_auth_client';

    public function __construct(
        private readonly DashboardAccessService $dashboardAccess,
    ) {}

    public function redirect(Request $request)
    {
        // Mobile app hits this with ?client=mobile ; web app hits it with no param.
        $client = $request->query('client') === 'mobile' ? 'mobile' : 'web';
        session([self::SESSION_KEY => $client]);

        return Socialite::driver('google')
            ->with(['prompt' => 'select_account'])
            ->redirect();
    }

    public function callback()
    {
        $isMobile = session(self::SESSION_KEY) === 'mobile';
        session()->forget(self::SESSION_KEY);

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (InvalidStateException $e) {
            return $this->fail($isMobile, 'Google sign-in session expired. Please try again.');
        } catch (\Throwable $e) {
            return $this->fail($isMobile, 'Unable to sign in with Google. Please try again.');
        }

        $email = strtolower($googleUser->getEmail());
        Log::info('Checking domain', ['email' => $email]);

        if (! Str::endsWith($email, '@up.edu.ph')) {
            return $this->fail($isMobile, 'Please sign in using your official up.edu.ph email address.');
        }

        $user = User::where('email', $email)->first();

        if (! $user) {
            return $this->fail($isMobile, 'No account found for this email. Please contact your administrator.');
        }

        if (isset($user->status) && ! $user->status) {
            return $this->fail($isMobile, 'Your account is inactive. Please contact your administrator.');
        }

        Auth::login($user, false);

        activity()
            ->causedBy($user)
            ->event('login')
            ->log('User logged in via Google' . ($isMobile ? ' (mobile)' : ''));

        if ($isMobile) {
            $token = $user->createToken('mobile')->plainTextToken;

            Log::info('Token created, rendering redirect view', ['user_id' => $user->id]);

            return view('auth.mobile-redirect', [
                'deepLink' => 'upcebuims://auth/callback?token=' . urlencode($token),
            ]);
        }

        return $this->resolveWebRedirect($user);
    }

    private function resolveWebRedirect(User $user)
    {
        // Only roles that actually map to a distinct dashboard matter for the choice
        $availableDashboards = $this->dashboardAccess->optionsFor($user);

        if ($availableDashboards->count() > 1) {
            // Multiple distinct dashboards available — let the user choose
            session(['role_dashboard_options' => $availableDashboards->all()]);

            return redirect()->route('auth.select-dashboard');
        }

        try {
            $fallback = $this->dashboardAccess->defaultRouteFor($user);
        } catch (\Throwable $e) {
            Log::error('Permission check failed during Google login: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'trace' => $e->getTraceAsString(),
            ]);

            $fallback = 'user.dashboard';
        }

        return redirect()->route($fallback);
    }

    private function fail(bool $isMobile, string $message)
    {
        if ($isMobile) {
            return view('auth.mobile-redirect', [
                'deepLink' => 'upcebuims://auth/callback?error=' . urlencode($message),
            ]);
        }

        return redirect()->route('login')->withErrors([
            'sso' => $message,
        ]);
    }
}
