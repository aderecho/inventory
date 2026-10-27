<?php

namespace App\Http\Controllers;

use App\Exceptions\InventoryImportException;
use App\Http\Requests\CategoryImportRequest;
use Illuminate\Http\Request;
use App\Services\CategoryExportService;
use App\Services\CategoryImportService;
use App\Services\CategoriesService;
use App\Http\Requests\CategoriesStoreRequest;
use App\Http\Requests\CategoriesUpdateRequest;
use App\Models\ItemClassification;

class Categories extends Controller
{
    public function __construct(
        protected CategoriesService $categoriesService
    ) {}

    public function categories(Request $request)
    {
        $search = $request->input('search');
        $costRange = $request->input('cost_range');
        $status = $request->input('status');

        return inertia('Categories', [
            'categories' => $this->categoriesService->filterAndPaginateCategories($search, $costRange, $status),
        ]);
    }

    public function store(CategoriesStoreRequest $request)
    {
        $this->categoriesService->createCategories(
            $request->validated()
        );

        return redirect()->back()->with('success', 'Categories added successfully.');
    }

    public function update(CategoriesUpdateRequest $request, $id)
    {
        $this->categoriesService->updateCategories($id, $request->validated());

        return redirect()->back()->with('success', 'Supplier updated successfully.');
    }

    public function destroy($id)
    {
        $supplier = ItemClassification::findOrFail($id);
        $supplier->delete();

        return redirect()->back()->with('success', 'Supplier deleted successfully.');
    }

    /**
     * Archives every selected category, one at a time so each keeps its
     * model events and activity log entry.
     */
    public function batchDelete(Request $request)
    {
        $data = $request->validate([
            'ids'   => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $categories = ItemClassification::whereIn('id', $data['ids'])->get();

        foreach ($categories as $category) {
            $category->delete();
        }

        return back()->with(
            'success',
            $categories->count() === 1
                ? 'Category archived successfully.'
                : $categories->count() . ' categories archived successfully.'
        );
    }

    /**
     * Batch Add: imports categories and returns a per-row report.
     */
    public function import(CategoryImportRequest $request, CategoryImportService $importer)
    {
        try {
            $result = $importer->import(
                $request->file('file'),
                $request->boolean('dry_run'),
            );
        } catch (InventoryImportException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($result);
    }

    /**
     * Downloads the fixed sample workbook used as the Batch Add template.
     */
    public function exportSample(CategoryExportService $exporter)
    {
        return response()
            ->download($exporter->sampleToFile(), 'Category_Batch_Add_Sample.xlsx', [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])
            ->deleteFileAfterSend(true);
    }

    /**
     * Exports every category in the database using the sample's columns.
     */
    public function exportExcel(Request $request, CategoryExportService $exporter)
    {
        $format = $request->query('format') === 'csv' ? 'csv' : 'xlsx';

        $contentType = $format === 'csv'
            ? 'text/csv'
            : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

        return response()
            ->download(
                $exporter->exportToFile($format),
                'Categories_' . now()->format('Y-m-d_His') . '.' . $format,
                ['Content-Type' => $contentType],
            )
            ->deleteFileAfterSend(true);
    }
}
