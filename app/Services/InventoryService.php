<?php

namespace App\Services;

use App\Models\UserProfile;
use Illuminate\Http\Request;
use App\Models\InventoryItem;
use App\Models\AcknowledgementItem;
use App\Models\ItemHistoryLocation;
use App\Models\AcknowledgementReceipt;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\Spreadsheet;


class InventoryService
{
    public function filterAndPaginateInventory(
        ?string $search = null,
        ?string $costRange = null,
        int|string|null $status = null,
        ?string $acknowledgementStatus = null,
        ?int $roomId = null,
        int $perPage = 10
    ) {
        return InventoryItem::with([
            'itemClassification',
            'supplier',
            'latestAcknowledgementItem.accountablePerson',
            'acknowledgementHistory.accountablePerson',
            'acknowledgementHistory.acknowledgementReceipts',
            'latestHistoryLocation',
        ])
            ->when(
                $search,
                fn($query, $search) => $query->search($search)
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
                fn($query) => $query->where('status', $status)
            )
            ->when($acknowledgementStatus, function ($query, $acknowledgementStatus) {

                if ($acknowledgementStatus === 'with_acknowledgement') {
                    $query->whereHas('latestAcknowledgementItem');
                }

                if ($acknowledgementStatus === 'without_acknowledgement') {
                    $query->whereDoesntHave('latestAcknowledgementItem');
                }
            })
             ->when($roomId, function ($query, $roomId) {
                $query->whereHas('latestHistoryLocation', function ($q) use ($roomId) {
                    $q->where('room_id', $roomId);
                });
            })
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function getAdminProfiles()
    {
        $totalPermissions = Permission::count();

        $userIds = User::with('roles', 'permissions')
            ->get()
            ->filter(fn(User $user) => $user->getAllPermissions()->count() >= $totalPermissions)
            ->pluck('id');

        return UserProfile::whereIn('user_id', $userIds)->get();
    }

    public function createAcknowledgements(array $data)
    {
        $itemIds = $data['inventory_item_id'];

        $inventoryItems = InventoryItem::with('latestHistoryLocation')
            ->whereIn('id', $itemIds)
            ->get()
            ->keyBy('id');

        // Group item IDs by PO Number
        $groupedByPo = collect($itemIds)->groupBy(function ($itemId) use ($inventoryItems) {
            return $inventoryItems[$itemId]->po_number ?? 'unknown';
        });

        // e.g. "223-2026-06-123"
        $baseCategory = rtrim($data['category'], '-');
        $parts = explode('-', $baseCategory);
        $seriesNumber = (int) array_pop($parts);
        $prefix = implode('-', $parts) . '-';

        // Prevent duplicate category numbers
        $lastReceipt = AcknowledgementReceipt::where('category', 'like', $prefix . '%')
            ->orderByRaw('CAST(SUBSTRING_INDEX(category, "-", -1) AS UNSIGNED) DESC')
            ->first();

        $increment = $lastReceipt
            ? ((int) last(explode('-', $lastReceipt->category))) + 1
            : $seriesNumber;

        foreach ($groupedByPo as $poNumber => $groupedItemIds) {

            $category = $prefix . $increment;

            $ack = AcknowledgementReceipt::create([
                'issued_by_id' => $data['issued_by_id'],
                'category'     => $category,
                'created_by'   => $data['created_by'],
                'par_date'     => $data['par_date'],
                'remarks'      => $data['remarks'] ?? null,
            ]);

            foreach ($groupedItemIds as $itemId) {

                AcknowledgementItem::create([
                    'acknowledgement_id'    => $ack->id,
                    'inventory_item_id'     => $itemId,
                    'accountable_person_id' => $data['accountable_persons_id'],
                    'issued_by_id'          => $data['issued_by_id'],
                    'status'                => 1,
                ]);

                // Only log a new location if it actually changed
                $currentLocation = $inventoryItems[$itemId]->latestHistoryLocation;

                if (!$currentLocation || $currentLocation->room_id != $data['room_id']) {
                    ItemHistoryLocation::create([
                        'inventory_item_id' => $itemId,
                        'room_id'           => $data['room_id'],
                    ]);
                }
            }

            $increment++;
        }
    }

    public function createInventoryItems(array $data): void
    {
        $base = rtrim($data['property_number'], '-');

        foreach ($data['serial_numbers'] as $index => $serialNumber) {
            $propertyNumber = $base . '-' . str_pad($index + 1, 3, '0', STR_PAD_LEFT);
            $description = $data['descriptions'][$index] ?? $data['description'] ?? null;

            $inventoryItem = InventoryItem::create([
                'item_classification_id' => $data['item_classification_id'],
                'supplier_id' => $data['supplier_id'],
                'invoice' => $data['invoice'],
                'fund_source' => $data['fund_source'],
                'item_name' => $data['item_name'],
                'brand' => $data['brand'],
                'model' => $data['model'],
                'description' => $description,
                'quantity' => 1,
                'unit' => $data['unit'],
                'unit_cost' => $data['unit_cost'],
                'total_amount' => $data['unit_cost'],
                'property_number' => $propertyNumber,
                'serial_number' => $serialNumber,
                'pr_number' => $data['pr_number'],
                'po_number' => $data['po_number'],
                'remarks' => $data['remarks'],
                'date_acquired' => $data['date_acquired'],
                'status' => $data['status'],
                'is_private' => $data['is_private'] ?? 0,
            ]);

            ItemHistoryLocation::create([
                'inventory_item_id' => $inventoryItem->id,
                'room_id' => $data['room_id'],
            ]);
        }
    }

    public function updateInventoryItem(int $id, array $data): void
    {
        $item = InventoryItem::findOrFail($id);

        $totalAmount = $data['quantity'] * $data['unit_cost'];

        // Normalize property_number: if frontend sent a prefix ending with '-',
        // re-append the existing suffix for this item (keep the same -NNN),
        // otherwise accept the provided full property number.
        $propertyNumberToSave = $data['property_number'] ?? $item->property_number;
        if (str_ends_with((string) ($data['property_number'] ?? ''), '-')) {
            $base = rtrim($data['property_number'], '-');

            $existing = $item->property_number ?? '';
            $suffix = '001';

            if (str_starts_with($existing, $base . '-')) {
                $parts = explode('-', $existing);
                $last = end($parts);
                if (preg_match('/^\d{3}$/', $last)) {
                    $suffix = $last;
                }
            }

            $propertyNumberToSave = $base . '-' . $suffix;
        }

        $item->update([
            'item_classification_id' => $data['item_classification_id'],
            'supplier_id' => $data['supplier_id'],
            'invoice' => $data['invoice'],
            'fund_source' => $data['fund_source'],
            'item_name' => $data['item_name'],
            'brand' => $data['brand'] ?? null,
            'model' => $data['model'] ?? null,
            // Prefer per-item descriptions if provided (descriptions[0] mirrors main description)
            'description' => $data['descriptions'][0] ?? $data['description'] ?? null,
            'quantity' => $data['quantity'],
            'unit' => $data['unit'],
            'unit_cost' => $data['unit_cost'],
            'total_amount' => $totalAmount,
            'property_number' => $propertyNumberToSave,
            'serial_number' => $data['serial_number'],
            'pr_number' => $data['pr_number'],
            'po_number' => $data['po_number'],
            'remarks' => $data['remarks'] ?? null,
            'date_acquired' => $data['date_acquired'],
            'status' => $data['status'] ?? 1,
            'is_private' => $data['is_private'] ?? 0,
        ]);

        $currentRoomId = $item->latestHistoryLocation?->room_id;

        if ($currentRoomId != $data['room_id']) {
            ItemHistoryLocation::create([
                'inventory_item_id' => $item->id,
                'room_id' => $data['room_id'],
            ]);
        }
    }

    public function updateCategory(array $itemIds, string $category): void
    {
        InventoryItem::whereIn('id', $itemIds)
            ->update(['category' => $category]);
    }

    public function convertToCsv(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls'
        ]);

        $file = $request->file('file');

        $spreadsheet = IOFactory::load($file->getRealPath());

        $csvTempPath = storage_path('app/converted_' . time() . '.csv');

        $writer = IOFactory::createWriter($spreadsheet, "Csv");
        $writer->setDelimiter(",");
        $writer->setEnclosure('"');
        $writer->setSheetIndex(0);
        $writer->save($csvTempPath);

        return response()->download($csvTempPath)->deleteFileAfterSend(true);
    }

    public function importCsv(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:csv,txt',
        ]);

        $file = $request->file('file');

        $spreadsheet = IOFactory::load($file->getRealPath());
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        $header = array_map('trim', $rows[0]);
        unset($rows[0]);

        $errors = [];
        $imported = [];

        foreach ($rows as $index => $row) {
            $data = array_combine($header, $row);

            $validator = Validator::make($data, [
                'item_classification_id' => 'required|integer',
                'supplier_id' => 'required|integer',
                'invoice' => 'required|string|max:50',
                'fund_source' => 'required|string|max:50',
                'item_name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'category' => 'nullable|string|max:255',
                'quantity' => 'required|integer|min:1',
                'unit' => 'required|string|max:50',
                'unit_cost' => 'required|numeric',
                'serial_numbers' => 'required|string', // CSV: comma-separated
                'property_number' => 'required|string|max:50',
                'pr_number' => 'required|string|max:50',
                'po_number' => 'required|string|max:50',
                'remarks' => 'required|string|max:50',
                'date_acquired' => 'required|date',
                'status' => 'nullable|string|max:50',
            ]);

            if ($validator->fails()) {
                $errors[$index + 2] = $validator->errors()->all();
                continue;
            }

            $serialArray = array_map('trim', explode(',', $data['serial_numbers']));

            $data['serial_number'] = implode(',', $serialArray);
            $data['total_amount'] = $data['unit_cost'] * $data['quantity'];

            unset($data['serial_numbers']);

            $imported[] = InventoryItem::create($data);
        }

        if (!empty($errors)) {
            return back()->with('import_errors', $errors);
        }

        return back()->with('success', count($imported) . ' items imported successfully.');
    }

    public function exportCsv()
    {
        $items = InventoryItem::all();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Header row
        $header = [
            'item_classification_id',
            'supplier_id',
            'invoice',
            'fund_source',
            'item_name',
            'description',
            'category',
            'quantity',
            'unit',
            'unit_cost',
            'serial_number',
            'property_number',
            'pr_number',
            'po_number',
            'remarks',
            'date_acquired',
            'status',
            'total_amount'
        ];
        $sheet->fromArray($header, null, 'A1');

        // Data rows
        $rowNumber = 2;
        foreach ($items as $item) {
            $sheet->fromArray([
                $item->item_classification_id,
                $item->supplier_id,
                $item->invoice,
                $item->fund_source,
                $item->item_name,
                $item->description,
                $item->category,
                $item->quantity,
                $item->unit,
                $item->unit_cost,
                $item->serial_number,
                $item->property_number,
                $item->pr_number,
                $item->po_number,
                $item->remarks,
                $item->date_acquired,
                $item->status,
                $item->total_amount,
            ], null, 'A' . $rowNumber);
            $rowNumber++;
        }

        $filename = 'inventory_export_' . date('Y-m-d_H-i-s') . '.csv';
        $tempPath = storage_path('app/' . $filename);

        $writer = new Csv($spreadsheet);
        $writer->setDelimiter(',');
        $writer->setEnclosure('"');
        $writer->save($tempPath);

        return response()->download($tempPath)->deleteFileAfterSend(true);
    }
}
