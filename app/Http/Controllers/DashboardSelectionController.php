<?php

namespace App\Http\Controllers;

use App\Services\DashboardAccessService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class DashboardSelectionController extends Controller
{
    public function show(Request $request)
    {
        $options = session('role_dashboard_options', []);

        if (empty($options)) {
            return redirect()->route('dashboard.index'); // fallback
        }

        // Staff and admins share the main dashboard, so the card is worded for
        // the account opening it instead of always saying "Admin".
        $roles = $request->user()
            ? $request->user()->getRoleNames()->map(fn ($role) => strtolower($role))->all()
            : [];

        $staffView = in_array('staff', $roles, true)
            && ! in_array('admin', $roles, true)
            && ! in_array('super-admin', $roles, true);

        return Inertia::render('Auth/SelectDashboard', [
            'options'   => $options,
            'staffView' => $staffView,
        ]);
    }

    public function choose(Request $request)
    {
        $options = session('role_dashboard_options', []);

        $request->validate([
            'route' => ['required', Rule::in($options)],
        ]);

        session()->forget('role_dashboard_options');

        return redirect()->route($request->route);
    }

    /**
     * "Switch workspace" from the settings menu: rebuild the dashboards the
     * account may open and hand it back to the picker.
     */
    public function switch(Request $request, DashboardAccessService $dashboards)
    {
        $options = $dashboards->optionsFor($request->user());

        if ($options->count() > 1) {
            session(['role_dashboard_options' => $options->all()]);

            return redirect()->route('auth.select-dashboard');
        }

        return redirect()->route(
            $options->first() ?? $dashboards->defaultRouteFor($request->user())
        );
    }
}
