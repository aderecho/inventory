<?php

namespace App\Services;

use App\Models\AccessToken;
use Illuminate\Http\Request;

class EmbedDashboardService
{
    public function __construct(
        protected DashboardService $dashboardService
    ) {}

    public function validateAccessToken(string $token): AccessToken
    {
        $accessToken = AccessToken::with('apiClient')
            ->where(
                'token_hash',
                hash('sha256', $token)
            )
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->first();

        abort_unless(
            $accessToken,
            401,
            'Invalid or expired access token.'
        );

        $client = $accessToken->apiClient;

        abort_unless(
            $client && $client->is_active,
            403,
            'This integration is inactive.'
        );

        $accessToken->update([
            'last_used_at' => now(),
        ]);

        return $accessToken;
    }

    public function getDashboardData(Request $request): array
    {
        $availableYears = $this->dashboardService
            ->getAvailableYears();

        $selectedYear = $this->dashboardService
            ->resolveSelectedYear(
                $request,
                $availableYears
            );

        return [
            'stats' => $this->dashboardService
                ->getStats(),

            'classificationChartData' => $this->dashboardService
                ->getClassificationChartData(),

            'acquisitionsByClassification' => $this->dashboardService
                ->getAcquisitionsByClassification(
                    $selectedYear
                ),

            'icsParChartData' => $this->dashboardService
                ->getIcsParChartData(),

            'accountablePersonChartData' => $this->dashboardService
                ->getAccountablePersonChartData(),

            'availableYears' => $availableYears,

            'selectedYear' => (int) $selectedYear,
        ];
    }
}