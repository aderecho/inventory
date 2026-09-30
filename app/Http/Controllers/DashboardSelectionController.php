<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class DashboardSelectionController extends Controller
{
    public function show()
    {
        $options = session('role_dashboard_options', []);

        if (empty($options)) {
            return redirect()->route('dashboard.index'); // fallback
        }

        return Inertia::render('Auth/SelectDashboard', ['options' => $options]);
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
}