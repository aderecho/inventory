<?php

namespace App\Services;

use App\Models\AcknowledgementItem;
use App\Models\AcknowledgementReceipt;
use App\Models\InventoryItem;
use App\Models\ItemClassification;
use App\Models\Organization;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardService
{
    protected int $parThreshold = 50000;

    public function filterAndPaginateInventory(
        ?string $search = null,
        int $perPage = 10
    ) {
        return InventoryItem::with('supplier')
            ->when(
                $search,
                fn($query, $search) =>
                $query->search($search)
            )
            ->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Build the full payload the Dashboard page needs, given the incoming request.
     */
    public function getDashboardData(Request $request): array
    {
        $search = $request->input('search');

        $availableYears = $this->getAvailableYears();
        $selectedYear = $this->resolveSelectedYear($request, $availableYears);

        return [
            'items' => $this->filterAndPaginateInventory($search),

            'stats' => $this->getStats(),

            'classificationChartData' => $this->getClassificationChartData(),
            'acquisitionsByClassification' => $this->getAcquisitionsByClassification($selectedYear),
            'icsParChartData' => $this->getIcsParChartData(),
            'accountablePersonChartData' => $this->getAccountablePersonChartData(),
            'organizationChartData' => $this->getOrganizationChartData(),
            'availableYears' => $availableYears,
            'selectedYear' => (int) $selectedYear,

            'searchItem' => $search,
        ];
    }

    public function getClassificationChartData()
    {
        return ItemClassification::withCount('inventoryItems')
            ->get()
            ->map(fn($c) => [
                'id' => $c->id,
                'classification_name' => $c->classification_name,
                'total_items' => $c->inventory_items_count,
            ]);
    }

    public function getOrganizationChartData()
    {
        return Organization::withCount('userProfiles')
            ->get()
            ->map(fn($org) => [
                'id' => $org->id,
                'organization_name' => $org->name,
                'total_user_profiles' => $org->user_profiles_count,
            ]);
    }

    public function getAvailableYears()
    {
        return InventoryItem::whereNotNull('date_acquired')
            ->selectRaw('DISTINCT YEAR(date_acquired) as year')
            ->orderBy('year')
            ->pluck('year');
    }

    /**
     * Resolve the year to use for the acquisitions chart: whatever the
     * request asked for, falling back to the most recent available year
     * if the requested one isn't valid.
     */
    public function resolveSelectedYear(Request $request, $availableYears)
    {
        $selectedYear = $request->input('acquisition_year', $availableYears->last());

        if (!$availableYears->contains((int) $selectedYear)) {
            $selectedYear = $availableYears->last();
        }

        return $selectedYear;
    }

    public function getAcquisitionsByClassification($selectedYear)
    {
        $acquisitionRows = InventoryItem::selectRaw('item_classification_id, MONTH(date_acquired) as month, COUNT(*) as total')
            ->whereYear('date_acquired', $selectedYear)
            ->whereNotNull('item_classification_id')
            ->groupBy('item_classification_id', 'month')
            ->get()
            ->groupBy('item_classification_id');

        return ItemClassification::all()->map(function ($classification) use ($acquisitionRows) {
            $monthlyTotals = array_fill(1, 12, 0);

            $rows = $acquisitionRows->get($classification->id, collect());

            foreach ($rows as $row) {
                $monthlyTotals[$row->month] = $row->total;
            }

            return [
                'classification_name' => $classification->classification_name,
                'monthly_totals' => array_values($monthlyTotals),
            ];
        })
            ->filter(fn($c) => array_sum($c['monthly_totals']) > 0)
            ->values();
    }

    public function getIcsParChartData()
    {
        $icsParCounts = AcknowledgementItem::selectRaw('acknowledgement_id, SUM(COALESCE(inventory_items.unit_cost, 0) * COALESCE(inventory_items.quantity, 1)) as receipt_total')
            ->join('inventory_items', 'inventory_items.id', '=', 'acknowledgement_items.inventory_item_id')
            ->groupBy('acknowledgement_id')
            ->get()
            ->map(fn($row) => $row->receipt_total >= $this->parThreshold ? 'PAR' : 'ICS')
            ->countBy()
            ->toArray();

        return [
            ['label' => 'ICS', 'count' => $icsParCounts['ICS'] ?? 0],
            ['label' => 'PAR', 'count' => $icsParCounts['PAR'] ?? 0],
        ];
    }

    /**
     * Accountable person assignment, based on each item's latest
     * acknowledgement record.
     */
    public function getAccountablePersonChartData()
    {
        $totalItems = InventoryItem::count();

        $assignedCount = InventoryItem::whereHas('latestAcknowledgementItem', function ($q) {
            $q->whereNotNull('accountable_person_id');
        })->count();

        $unassignedCount = $totalItems - $assignedCount;

        return [
            ['label' => 'Assigned', 'count' => $assignedCount],
            ['label' => 'Unassigned', 'count' => $unassignedCount],
        ];
    }

    public function getStats(): array
    {
        return [
            'item_classifications' => ItemClassification::count(),
            'items' => InventoryItem::count(),
            'receipts' => AcknowledgementReceipt::count(),
            'suppliers' => Supplier::count(),
            'users' => User::count(),

            'items_per_month' => InventoryItem::selectRaw('MONTH(created_at) as month, COUNT(*) as total')
                ->whereYear('created_at', now()->year)
                ->groupBy('month')
                ->orderBy('month')
                ->pluck('total', 'month'),

            'receipts_per_month' => AcknowledgementReceipt::selectRaw('MONTH(created_at) as month, COUNT(*) as total')
                ->whereYear('created_at', now()->year)
                ->groupBy('month')
                ->orderBy('month')
                ->pluck('total', 'month'),
        ];
    }
}