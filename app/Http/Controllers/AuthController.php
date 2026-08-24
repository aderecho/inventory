<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserLoginRequest;
use App\Models\User;
use Inertia\Inertia;
use App\Models\UserProfile;
use Illuminate\Http\Request;
use App\Services\AuthService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function __construct(
        protected AuthService $authService,
    ){}
    public function login()
    {
        return Inertia::render('Auth/Login');
    }

    public function authenticate(Request $request)
    {
        return $this->authService->authenticate($request);
    }

    public function logout(Request $request)
    {
        return $this->authService->logout($request);
    }
}
