<?php

namespace App\Http\Controllers;

use App\Models\EmbedToken;
use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

namespace App\Http\Controllers;

use App\Models\EmbedToken;
use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class EmbedDashboardController extends Controller
{
    public function __construct(protected DashboardService $dashboardService) {}

    public function show(Request $request, string $token)
    {
        $embedToken = EmbedToken::where('token', $token)->firstOrFail();

        abort_if($embedToken->isExpired(), 410, 'This embed link has expired.');

        $origin = $request->headers->get('origin') ?? $request->headers->get('referer');

        if (!empty($embedToken->allowed_domains) && $origin) {
            $allowed = collect($embedToken->allowed_domains)
                ->contains(fn ($domain) => Str::startsWith($origin, $domain));

            abort_unless($allowed, 403, 'This domain is not permitted to embed this dashboard.');
        }

        $availableYears = $this->dashboardService->getAvailableYears();
        $selectedYear = $this->dashboardService->resolveSelectedYear($request, $availableYears);

        $data = [
            'stats' => $this->dashboardService->getStats(),
            'classificationChartData' => $this->dashboardService->getClassificationChartData(),
            'acquisitionsByClassification' => $this->dashboardService->getAcquisitionsByClassification($selectedYear),
            'icsParChartData' => $this->dashboardService->getIcsParChartData(),
            'accountablePersonChartData' => $this->dashboardService->getAccountablePersonChartData(),
            'organizationChartData' => $this->dashboardService->getOrganizationChartData(),
            'availableYears' => $availableYears,
            'selectedYear' => (int) $selectedYear,
            'embedToken' => $token,
        ];

        return Inertia::render('Embed/Dashboard', $data)
            ->toResponse($request)
            ->withHeaders([
                'Content-Security-Policy' => 'frame-ancestors ' .
                    ($embedToken->allowed_domains ? implode(' ', $embedToken->allowed_domains) : "'self'") . ';',
            ]);
    }
}