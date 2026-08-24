<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;

class GoogleController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('google')
            ->with([
                'prompt' => 'select_account',
            ])
            ->redirect();
    }

    public function callback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (InvalidStateException $e) {
            return redirect()->route('login')->withErrors([
                'sso' => 'Google sign-in session expired. Please try again.',
            ]);
        } catch (\Throwable $e) {
            return redirect()->route('login')->withErrors([
                'sso' => 'Unable to sign in with Google. Please try again.',
            ]);
        }

        $email = strtolower($googleUser->getEmail());
        Log::info('Checking domain', ['email' => $email]);

        if (! Str::endsWith($email, '@up.edu.ph')) {
            return redirect()->route('login')->withErrors([
                'sso' => 'Please sign in using your official up.edu.ph email address.',
            ]);
        }

        $user = User::where('email', $email)->first();

        if (! $user) {
            return redirect()->route('login')->withErrors([
                'sso' => 'No account found for this email. Please contact your administrator.',
            ]);
        }

        if (isset($user->status) && ! $user->status) {
            return redirect()->route('login')->withErrors([
                'sso' => 'Your account is inactive. Please contact your administrator.',
            ]);
        }

        Auth::login($user, false);

        // Audit Log
        activity()
            ->causedBy($user)
            ->event('login')
            ->log('User logged in via Google');

        try {
            $hasDashboardAccess = $user->can('view dashboard');
        } catch (\Throwable $e) {
            Log::error('Permission check failed during Google login: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'trace' => $e->getTraceAsString(),
            ]);

            $hasDashboardAccess = false;
        }

        return $hasDashboardAccess
            ? redirect()->route('dashboard.index')
            : redirect()->route('user.dashboard');
    }
}
