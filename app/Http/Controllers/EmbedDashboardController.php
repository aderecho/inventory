<?php

namespace App\Http\Controllers;

use App\Services\EmbedDashboardService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EmbedDashboardController extends Controller
{
    public function __construct(
        protected EmbedDashboardService $embedDashboardService
    ) {}

    public function show(Request $request)
    {
        $token = $request->query('access_token');

        abort_unless(
            $token,
            401,
            'Missing access token.'
        );

        $this->embedDashboardService
            ->validateAccessToken($token);

        $data = $this->embedDashboardService
            ->getDashboardData($request);

        $response = Inertia::render(
            'Embed/Dashboard',
            $data
        )->toResponse($request);

        $response->headers->set(
            'Content-Security-Policy',
            'frame-ancestors *;'
        );

        return $response;
    }
}