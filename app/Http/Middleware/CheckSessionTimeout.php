<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckSessionTimeout
{
    protected int $absoluteTimeout;      // session cap, from session.lifetime
    protected int $idleTimeout;          // inactivity logout
    protected int $regenerateInterval;   // silent id + CSRF rotation

    public function __construct()
    {
        $this->absoluteTimeout = (int) config('session.lifetime') * 60;
        $this->idleTimeout = (int) config('session.idle_timeout', 900);
        $this->regenerateInterval = (int) config('session.regenerate_interval', 1800);
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
