<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Services\DashboardService;

class DashboardController extends Controller
{
    public function __construct(
        protected DashboardService $dashboardService,
    ) {}

    public function searchBar(Request $request)
    {
        return Inertia::render('Dashboard', $this->dashboardService->getDashboardData($request));
    }
}