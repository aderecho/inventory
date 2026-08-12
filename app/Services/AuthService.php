<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    public function authenticate(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        try {
            if (!Auth::attempt($credentials)) {
                return back()->withErrors([
                    'email' => 'The provided credentials do not match our records.',
                ]);
            }

            $request->session()->regenerate();

            $user = Auth::user();

            if (!$user) {
                throw new \Exception('Authenticated but user instance is null.');
            }

            // Log successful login
            activity()
                ->causedBy($user)
                ->event('login')
                ->log('User logged in');

            // Clear any stale "intended" URL from before login so it can't
            // override the permission-based redirect below.
            $request->session()->forget('url.intended');

            return $user->can('view dashboard')
                ? redirect()->route('dashboard.index')
                : redirect()->route('user.dashboard');
        } catch (\Throwable $e) {
            \Log::error('Login/authorization error: ' . $e->getMessage(), [
                'email' => $credentials['email'] ?? null,
                'trace' => $e->getTraceAsString(),
            ]);

            if (Auth::check()) {
                return redirect()->route('user.dashboard')
                    ->withErrors([
                        'email' => 'Something went wrong loading your dashboard. Please try again.'
                    ]);
            }

            return back()->withErrors([
                'email' => 'Something went wrong while logging in. Please try again.',
            ]);
        }
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'first_name'         => ['required', 'string', 'max:255'],
            'last_name'          => ['required', 'string', 'max:255'],
            'middle_name'        => ['nullable', 'string', 'max:255'],
            'contact_number'     => ['required', 'string', 'max:20'],

            'email'              => ['required', 'email', 'unique:users,email'],
            'password'           => ['required', 'min:8', 'confirmed'],
        ]);

        DB::transaction(function () use ($validated) {

            $user = User::create([
                'email'    => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);

            UserProfile::create([
                'user_id'        => $user->id,
                'first_name'     => $validated['first_name'],
                'last_name'      => $validated['last_name'],
                'middle_name'    => $validated['middle_name'] ?? null,
                'contact_number' => $validated['contact_number'],
            ]);
        });

        return redirect()->back();
    }

    public function logout(Request $request)
    {
        if (Auth::check()) {
            $user = Auth::user();

            activity()
                ->causedBy($user)
                ->event('logout')
                ->log('user logged out');
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}