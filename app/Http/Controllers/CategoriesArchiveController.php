<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\CategoriesService;
use App\Models\ItemClassification;

class CategoriesArchiveController extends Controller
{
     public function __construct(
        protected CategoriesService $categoriesService
    ) {}

    public function index(Request $request)
    {
        $search = $request->input('search');

        return inertia('CategoriesDisposal', [
            'categories' => $this->categoriesService->filterAndPaginateArchiveCategories($search),
        ]);
    }

    public function restore($id)
    {
        $categories = ItemClassification::onlyTrashed()->findOrFail($id);

        $categories->restore();

        return back()->with([
            'success' => 'Category restored successfully.',
        ]);
    }

    public function forceDelete($id)
    {
        $categories = ItemClassification::onlyTrashed()->findOrFail($id);

        $categories->forceDelete();

        return back()->with([
            'success' => 'Category permanently deleted.',
        ]);
    }

    public function batchRestore(Request $request)
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $categories = ItemClassification::onlyTrashed()
            ->whereIn('id', $data['ids'])
            ->get();

        foreach ($categories as $category) {
            $category->restore();
        }

        return back()->with(
            'success',
            $categories->count() === 1
                ? 'Category restored successfully.'
                : $categories->count() . ' categories restored successfully.'
        );
    }

    public function batchForceDelete(Request $request)
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $categories = ItemClassification::onlyTrashed()
            ->whereIn('id', $data['ids'])
            ->get();

        foreach ($categories as $category) {
            $category->forceDelete();
        }

        return back()->with(
            'success',
            $categories->count() === 1
                ? 'Category permanently deleted.'
                : $categories->count() . ' categories permanently deleted.'
        );
    }
}
