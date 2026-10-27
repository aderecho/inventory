<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;

class DashboardAccessService
{
    /**
     * Roles that map to a dashboard of their own. Roles that are missing here
     * share the default dashboard instead of adding another choice.
     */
    private const ROLE_DASHBOARDS = [
        'admin'       => 'dashboard.index',
        'super-admin' => 'dashboard.index',
        'staff'       => 'dashboard.index',
        'user'        => 'user.dashboard',
    ];

    /**
     * The distinct dashboards an account may open, in a stable order.
     *
     * @return Collection<int, string>
     */
    public function optionsFor(?User $user): Collection
    {
        if (! $user) {
            return collect();
        }

        return $user->getRoleNames()
            ->map(fn ($role) => strtolower((string) $role))
            ->filter(fn ($role) => isset(self::ROLE_DASHBOARDS[$role]))
            ->map(fn ($role) => self::ROLE_DASHBOARDS[$role])
            ->unique()
            ->values();
    }

    /**
     * Where an account lands when it cannot choose: the dashboard it can view,
     * otherwise its personal one.
     */
    public function defaultRouteFor(?User $user): string
    {
        return $user?->can('view dashboard')
            ? 'dashboard.index'
            : 'user.dashboard';
    }
}

