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
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            return redirect()->route('dashboard.index');
        }

        return back()->withErrors([
            'email' => 'Invalid email or password.',
        ])->withInput($request->only('email'));
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
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
