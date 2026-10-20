<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SupplierService;
use App\Models\Supplier;

class SupplierArchiveController extends Controller
{
    public function __construct(
        protected SupplierService $supplierService
    ) {}

    public function index(Request $request)
    {
        $search = $request->input('search');

        return inertia('SupplierDisposal', [
            'suppliers' => $this->supplierService->filterAndPaginateArchiveSuppliers($search),
        ]);
    }

    public function restore($id)
    {
        $supplier = Supplier::onlyTrashed()->findOrFail($id);

        $supplier->restore();

        return back()->with([
            'success' => 'Supplier restored successfully.',
        ]);
    }

    public function forceDelete($id)
    {
        $supplier = Supplier::onlyTrashed()->findOrFail($id);

        $supplier->forceDelete();

        return back()->with([
            'success' => 'Supplier permanently deleted.',
        ]);
    }

    public function batchRestore(Request $request)
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $suppliers = Supplier::onlyTrashed()->whereIn('id', $data['ids'])->get();

        foreach ($suppliers as $supplier) {
            $supplier->restore();
        }

        return back()->with(
            'success',
            $suppliers->count() === 1
                ? 'Supplier restored successfully.'
                : $suppliers->count() . ' suppliers restored successfully.'
        );
    }

    public function batchForceDelete(Request $request)
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $suppliers = Supplier::onlyTrashed()->whereIn('id', $data['ids'])->get();

        foreach ($suppliers as $supplier) {
            $supplier->forceDelete();
        }

        return back()->with(
            'success',
            $suppliers->count() === 1
                ? 'Supplier permanently deleted.'
                : $suppliers->count() . ' suppliers permanently deleted.'
        );
    }
}
