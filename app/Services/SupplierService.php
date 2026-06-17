<?php

namespace App\Services;

use App\Models\Supplier;

class SupplierService
{
    public function filterAndPaginateSuppliers(
        ?string $search = null,
        ?string $costRange = null,
        int|string|null $status = null,
        int $perPage = 10
    ) {
        return Supplier::with('inventoryItems')
            ->when(
                $search,
                fn($query, $search) =>
                $query->search($search)
            )
            ->when($costRange, function ($query, $costRange) {

                [$min, $max] = array_pad(explode('-', $costRange), 2, null);

                $min = $min !== '' ? $min : null;
                $max = $max !== '' ? $max : null;

                if ($min !== null && $max !== null) {
                    $query->whereBetween('unit_cost', [(float) $min, (float) $max]);
                } elseif ($min !== null) {
                    $query->where('unit_cost', '>=', (float) $min);
                } elseif ($max !== null) {
                    $query->where('unit_cost', '<=', (float) $max);
                }
            })
            ->when(
                !is_null($status),
                fn($query) =>
                $query->where('status', $status)
            )
            ->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function filterAndPaginateArchiveSuppliers(
        ?string $search = null,
        int $perPage = 10
    ) {
        return Supplier::onlyTrashed()
            ->when($search, fn($query, $search) => $query->search($search))
            ->orderBy('deleted_at', 'desc')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function createSupplier(array $data): Supplier
    {
        return Supplier::create([
            'supplier_name' => $data['supplier_name'],
            'contact_no' => $data['contact_no'] ?? null,
            'email' => $data['email'] ?? null,
            'address' => $data['address'] ?? null,
            'status' => 1,
        ]);
    }

    public function updateSupplier(int $id, array $data): void
    {
        $supplier = Supplier::findOrFail($id);

        $supplier->update([
            'supplier_name' => $data['supplier_name'],
            'contact_no' => $data['contact_no'] ?? null,
            'email' => $data['email'] ?? null,
            'address' => $data['address'] ?? null,
        ]);
    }
}
