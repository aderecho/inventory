<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckSessionTimeout
{
    protected int $absoluteTimeout;      // 2 hours - hard session cap
    protected int $idleTimeout = 900;    // 15 minutes - inactivity logout
    protected int $regenerateInterval = 1800; // 30 minutes - token refresh

    public function __construct()
    {
        $this->absoluteTimeout = config('session.lifetime') * 60;
    }

    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            $now = time();

            // 1. Absolute session lifetime — hard cap regardless of activity
            $loginTime = session('login_time', $now);
            if (($now - $loginTime) > $this->absoluteTimeout) {
                return $this->forceLogout($request, 'Your session has expired. Please log in again.');
            }

            // 2. Idle timeout — based on last real activity
            $lastActivity = session('last_activity', $now);
            if (($now - $lastActivity) > $this->idleTimeout) {
                return $this->forceLogout($request, 'You were logged out due to inactivity.');
            }

            // 3. Periodic token refresh — fixation mitigation, silent to user
            if (! session()->has('last_regenerated')) {
                session(['last_regenerated' => $now]);
            }
            $lastRegenerated = session('last_regenerated');
            if (($now - $lastRegenerated) > $this->regenerateInterval) {
                $request->session()->regenerate(true);
                session(['last_regenerated' => $now]);
            }

            // Set login_time once, on first authenticated request
            if (! session()->has('login_time')) {
                session(['login_time' => $now]);
            }

            session(['last_activity' => $now]);
        }

        return $next($request);
    }

    protected function forceLogout(Request $request, string $message)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('message', $message);
    }
}