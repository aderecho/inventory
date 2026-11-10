<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Activitylog\Models\Activity;

class AuditLogsController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => 'nullable|string|max:255',
            'log_name' => 'nullable|string|max:100',
            'event' => 'nullable|string|max:50',
            'user_id' => 'nullable|integer',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
        ]);

        $activities = Activity::with('causer.userProfiles')
            ->when($filters['search'] ?? null, function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('description', 'like', "%{$search}%")
                        ->orWhere('log_name', 'like', "%{$search}%")
                        ->orWhere('event', 'like', "%{$search}%")
                        ->orWhere('subject_type', 'like', "%{$search}%")
                        ->orWhereHasMorph('causer', [User::class], function ($query) use ($search) {
                            $query->where('email', 'like', "%{$search}%")
                                ->orWhereHas('userProfiles', function ($query) use ($search) {
                                    $query->where('first_name', 'like', "%{$search}%")
                                        ->orWhere('middle_name', 'like', "%{$search}%")
                                        ->orWhere('last_name', 'like', "%{$search}%");
                                });
                        });
                });
            })
            ->when($filters['log_name'] ?? null, fn ($query, $logName) => $query->where('log_name', $logName))
            ->when($filters['event'] ?? null, fn ($query, $event) => $query->where('event', $event))
            ->when($filters['user_id'] ?? null, function ($query, $userId) {
                $query->where('causer_type', User::class)
                    ->where('causer_id', $userId);
            })
            ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '<=', $date))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $logNames = Activity::query()
            ->whereNotNull('log_name')
            ->distinct()
            ->orderBy('log_name')
            ->pluck('log_name');

        $events = Activity::query()
            ->whereNotNull('event')
            ->distinct()
            ->orderBy('event')
            ->pluck('event');

        $users = User::with('userProfiles')
            ->whereIn('id', Activity::query()
                ->where('causer_type', User::class)
                ->whereNotNull('causer_id')
                ->select('causer_id'))
            ->orderBy('email')
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->userProfiles?->full_name ?: $user->email,
            ])
            ->values();

        return Inertia::render('AuditLogs', [
            'activities' => $activities,
            'filters' => $filters,
            'logNames' => $logNames,
            'events' => $events,
            'users' => $users,
        ]);
    }
}
