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
}
