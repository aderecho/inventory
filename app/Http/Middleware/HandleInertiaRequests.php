<?php

namespace App\Http\Middleware;

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
            'flash' => [
                'plain_api_key' => fn() => $request->session()->get('plain_api_key'),
                'client_name' => fn() => $request->session()->get('client_name'),
                'success' => fn() => $request->session()->get('success'),
            ],
        ];
    }
}
