<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SupplierService;
use App\Models\Supplier;
use Illuminate\Routing\Controllers\Middleware;

class SupplierArchiveController extends Controller
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:view supplier archive', only: ['index']),
            new Middleware('can:restore supplier archive', only: ['restore']),
            new Middleware('can:force delete supplier archive', only: ['forceDelete']),
        ];
    }
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
