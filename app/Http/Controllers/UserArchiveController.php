<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\UserService;
use App\Models\User;

class UserArchiveController extends Controller
{
    public function __construct(
        protected UserService $userService
    ) {}

    public function index(Request $request)
    {
        $search = $request->input('search');

        return inertia('UserArchive', [
            'users' => $this->userService->filterAndPaginateArchiveUsers($search),
        ]);
    }

    public function restore($id)
    {
        $user = User::onlyTrashed()->findOrFail($id);

        $user->restore();

        return back()->with([
            'success' => 'User restored successfully.',
        ]);
    }

    public function forceDelete($id)
    {
        $user = User::onlyTrashed()->findOrFail($id);

        $user->forceDelete();

        return back()->with([
            'success' => 'User permanently deleted.',
        ]);
    }

    public function batchRestore(Request $request)
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $users = User::onlyTrashed()->whereIn('id', $data['ids'])->get();

        foreach ($users as $user) {
            $user->restore();
        }

        return back()->with(
            'success',
            $users->count() === 1
                ? 'User restored successfully.'
                : $users->count() . ' users restored successfully.'
        );
    }

    public function batchForceDelete(Request $request)
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $users = User::onlyTrashed()->whereIn('id', $data['ids'])->get();

        foreach ($users as $user) {
            $user->forceDelete();
        }

        return back()->with(
            'success',
            $users->count() === 1
                ? 'User permanently deleted.'
                : $users->count() . ' users permanently deleted.'
        );
    }
}
