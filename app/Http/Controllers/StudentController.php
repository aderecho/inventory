<?php

namespace App\Http\Controllers;

use App\Services\UserService;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function __construct(
        protected UserService $userService,
    ) {}

    public function index(Request $request)
    {
        $userId = $request->user()->id;

        return inertia('Students/Dashboard', [
            'user' => $this->userService->getAuthenticatedUser(),
            'stats' => $this->userService->getBorrowingStats($userId),
            'borrowedItems' => $this->userService->getBorrowedItems($userId),
            'returnedItems' => $this->userService->paginateReturnedItems($userId),
        ]);
    }
}
