<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Activitylog\Models\Activity;

class AuditLogsController extends Controller
{
    public function index()
    {
        $activities = Activity::with([
            'causer.userProfiles'
        ])
            ->latest()
            ->paginate(15);

        return Inertia::render('AuditLogs', [
            'activities' => $activities,
        ]);
    }
}
