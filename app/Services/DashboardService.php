<?php

namespace App\Services;

use App\Models\AcknowledgementItem;
use App\Models\AcknowledgementReceipt;
use App\Models\AssetCondition;
use App\Models\InventoryItem;
use App\Models\ItemClassification;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class DashboardService
{
    protected int $parThreshold = 50000;

    public function __construct(
        protected BorrowingService $borrowings,
    ) {}

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
            'availableYears' => $availableYears,
            'selectedYear' => (int) $selectedYear,

            'highlights' => $this->getHighlights(),

            'searchItem' => $search,
        ];
    }

    /**
     * The numbers people actually act on: what the inventory is worth, and
     * where the gaps are (items nobody is accountable for, items marked for
     * disposal, and receipts still waiting for approval).
     */
    public function getHighlights(): array
    {
        $borrowCounts = $this->borrowings->counts();

        return [
            'asset_value' => (float) InventoryItem::sum(
                DB::raw('COALESCE(total_amount, COALESCE(unit_cost, 0) * COALESCE(quantity, 1))')
            ),

            'borrowed_items' => $borrowCounts['borrowed'],

            'unassigned_items' => InventoryItem::whereDoesntHave('latestAcknowledgementItem')
                ->count(),

            // Disposed property carries the status itself, so nothing has to
            // match a condition name (or keep it spelled a certain way).
            'disposal_items' => InventoryItem::where('status', InventoryItem::STATUS_DISPOSED)->count(),

            // Still in stock, but the latest inspection says it should go.
            'for_disposal_items' => InventoryItem::where('status', '!=', InventoryItem::STATUS_DISPOSED)
                ->whereHas(
                    'latestInspection.assetCondition',
                    fn ($query) => $query->where('disposition', AssetCondition::DISPOSITION_FOR_DISPOSAL)
                )
                ->count(),

            // Approval status lives on the item itself, not on the receipt line.
            'pending_approvals' => InventoryItem::where('approval_status', 'pending')
                ->count(),
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
            'items' => InventoryItem::where('status', '!=', InventoryItem::STATUS_DISPOSED)->count(),
            'receipts' => AcknowledgementReceipt::count(),
            'suppliers' => Supplier::count(),
            'users' => User::count(),

            // Supporting counts for the summary cards: what sits behind each
            // headline number without opening the module.
            'suppliers_archived' => Supplier::onlyTrashed()->count(),

            // SPMO staff are identified by the department on their profile.
            'users_spmo' => User::whereHas(
                'userProfiles',
                fn ($query) => $query->where(
                    'primary_unit_division_department',
                    'UPC SUPPLY AND PROPERTY MANAGEMENT OFFICE'
                )
            )->count(),

            'items_per_month' => InventoryItem::selectRaw($this->monthExpression() . ' as month, COUNT(*) as total')
                ->whereYear('created_at', now()->year)
                ->groupBy('month')
                ->orderBy('month')
                ->pluck('total', 'month'),

            'receipts_per_month' => AcknowledgementReceipt::selectRaw($this->monthExpression() . ' as month, COUNT(*) as total')
                ->whereYear('created_at', now()->year)
                ->groupBy('month')
                ->orderBy('month')
                ->pluck('total', 'month'),
        ];
    }

    /**
     * MONTH() is MySQL-only; the test suite runs on SQLite.
     */
    private function monthExpression(): string
    {
        return DB::connection()->getDriverName() === 'sqlite'
            ? "CAST(strftime('%m', created_at) AS INTEGER)"
            : 'MONTH(created_at)';
    }
}
