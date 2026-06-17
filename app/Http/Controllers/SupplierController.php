<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;
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
}
