<?php

namespace App\Http\Controllers;

use App\Exceptions\InventoryImportException;
use App\Http\Requests\SupplierImportRequest;
use App\Models\Supplier;
use Illuminate\Http\Request;
use App\Services\SupplierExportService;
use App\Services\SupplierImportService;
use App\Services\SupplierService;
use App\Http\Requests\SupplierRequest;
use App\Http\Requests\SupplierUpdateRequest;


class SupplierController extends Controller
{
    public function __construct(
        protected SupplierService $supplierService
    ) {}

    public function suppliers(Request $request)
    {
        $search = $request->input('search');
        $costRange = $request->input('cost_range');
        $status = $request->input('status');

        return inertia('Suppliers', [
            'suppliers' => $this->supplierService->filterAndPaginateSuppliers($search, $costRange, $status),
        ]);
    }

    public function store(SupplierRequest $request)
    {
        $this->supplierService->createSupplier(
            $request->validated()
        );

        return redirect()->back()->with('success', 'Supplier added successfully.');
    }

    public function update(SupplierUpdateRequest $request, $id)
    {
        $this->supplierService->updateSupplier($id, $request->validated());

        return redirect()->back()->with('success', 'Supplier updated successfully.');
    }

    public function destroy($id)
    {
        $supplier = Supplier::findOrFail($id);
        $supplier->delete();

        return redirect()->back()->with('success', 'Supplier deleted successfully.');
    }

    /**
     * Archives every selected supplier, one at a time so each keeps its
     * model events and activity log entry.
     */
    public function batchArchive(Request $request)
    {
        $data = $request->validate([
            'ids'   => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $suppliers = Supplier::whereIn('id', $data['ids'])->get();

        foreach ($suppliers as $supplier) {
            $supplier->delete();
        }

        return back()->with(
            'success',
            $suppliers->count() === 1
                ? 'Supplier archived successfully.'
                : $suppliers->count() . ' suppliers archived successfully.'
        );
    }

    /**
     * Batch Add: imports suppliers and returns a per-row report.
     */
    public function import(SupplierImportRequest $request, SupplierImportService $importer)
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
    public function exportSample(SupplierExportService $exporter)
    {
        return response()
            ->download($exporter->sampleToFile(), 'Supplier_Batch_Add_Sample.xlsx', [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])
            ->deleteFileAfterSend(true);
    }

    /**
     * Exports every supplier in the database using the sample's columns.
     */
    public function exportExcel(Request $request, SupplierExportService $exporter)
    {
        $format = $request->query('format') === 'csv' ? 'csv' : 'xlsx';

        $contentType = $format === 'csv'
            ? 'text/csv'
            : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

        return response()
            ->download(
                $exporter->exportToFile($format),
                'Suppliers_' . now()->format('Y-m-d_His') . '.' . $format,
                ['Content-Type' => $contentType],
            )
            ->deleteFileAfterSend(true);
    }
}
