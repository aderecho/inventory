<?php

namespace App\Http\Middleware;

use App\Services\DashboardAccessService;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user'        => $request->user()?->load('userProfiles'),
                'role'        => $request->user()?->getRoleNames()->first(),
                'permissions' => $request->user()
                    ?->getAllPermissions()
                    ->reject(
                        fn($p) => $request->user()->permissions()
                            ->wherePivot('forbidden', true)
                            ->pluck('name')
                            ->contains($p->name)
                    )
                    ->pluck('name') ?? [],
            ],
            // Drives the "Switch workspace" entry in the settings menu.
            'workspaceOptions' => fn () => $request->user()
                ? app(DashboardAccessService::class)->optionsFor($request->user())->all()
                : [],
            'flash' => [
                'plain_api_key' => fn() => $request->session()->get('plain_api_key'),
                'client_name'   => fn() => $request->session()->get('client_name'),
                'success'       => fn() => $request->session()->get('success'),
                'error'         => fn() => $request->session()->get('error'),
                'warning'       => fn() => $request->session()->get('warning'),
            ],
            'sessionConfig' => [
                'idleTimeout' => (int) config('session.idle_timeout', 900) * 1000,
                'warningTime' => (int) config('session.idle_warning', 30) * 1000,
                'absoluteTimeout' => (int) config('session.lifetime') * 60 * 1000,
                'absoluteWarning' => (int) config('session.absolute_warning', 120) * 1000,
                // check.session.timeout stamps login_time on the request after
                // this one runs, so the first page load after signing in would
                // otherwise reach the browser without an absolute deadline.
                'startedAt' => $this->sessionStartedAt($request) * 1000,
            ],
        ];
    }

    /**
     * When the current session started, stamping it on the first authenticated
     * request so both the browser and check.session.timeout use one timestamp.
     */
    private function sessionStartedAt(Request $request): int
    {
        $startedAt = $request->session()->get('login_time');

        if ($startedAt) {
            return (int) $startedAt;
        }

        $startedAt = now()->timestamp;

        if ($request->user()) {
            $request->session()->put('login_time', $startedAt);
        }

        return $startedAt;
    }
}
