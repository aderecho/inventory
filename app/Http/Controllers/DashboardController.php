<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Services\DashboardService;
use App\Models\AcknowledgementReceipt;
use App\Models\Supplier;
use App\Models\User;
use App\Models\InventoryItem;
use App\Models\ItemClassification;

class DashboardController extends Controller
{
    public function __construct(
        protected DashboardService $dashboardService,
    ) {}

    public function searchBar(Request $request)
    {
        $search = $request->input('search');
        return Inertia::render('Dashboard', [
            'items' => $this->dashboardService->filterAndPaginateInventory($search),

            'stats' => [
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
            ],

            'searchItem' => $search,
        ]);
    }
}
