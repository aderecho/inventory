<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ItemArchivingService;
use App\Models\InventoryItem;

class ItemArchivingController extends Controller
{
    public function __construct(
        protected ItemArchivingService $itemArchivingService,
    ) {}
    public function index(Request $request)
    {
        $search = $request->input('search');
        $status = $request->input('status');

        $items = $this->itemArchivingService->filterAndPaginateArchive(
            $search,
            $status,
        );
        return inertia('ItemDisposal', [
            'items' => $items,
            'filters' => [
                'search' => $search,
                'status' => $status !== null ? (int) $status : null,
            ],
        ]);
    }

    public function restore($id)
    {
        $item = InventoryItem::onlyTrashed()->findOrFail($id);
        $item->restore();

        return back()->with('success', 'Item restored successfully.');
    }

    public function forceDelete($id)
    {
        $item = InventoryItem::onlyTrashed()->findOrFail($id);
        $item->forceDelete();

        return back()->with('success', 'Item permanently deleted.');
    }
}
