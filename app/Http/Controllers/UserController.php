<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\UserService;

class UserController extends Controller
{
    public function __construct(
        protected UserService $userService,
    ) {}

    public function index(Request $request)
    {
        $userId = auth()->id();
        return inertia('Users/Dashboard', [
            'user' => $this->userService->getAuthenticatedUser(),
            'items' => $this->userService->filterAndPaginateAssignedItems(
                $userId,
                $request->search
            ),
            'stats' => $this->userService->getDashboardStats($userId),
        ]);
    }
}
