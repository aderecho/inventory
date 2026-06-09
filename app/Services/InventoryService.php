<?php

namespace App\Services;

use App\Models\UserProfile;
use Illuminate\Http\Request;
use App\Models\InventoryItem;
use App\Models\AcknowledgementItem;
use App\Models\InventoryTransaction;
use App\Models\AcknowledgementReceipt;
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
        int $perPage = 10
    ) {
        return InventoryItem::with('itemClassification', 'supplier')
            ->when(
                $search,
                fn($query, $search) =>
                $query->search($search)
            )
            ->when($costRange, function ($query, $costRange) {

                // Always return 2 values; fill missing ones as null
                [$min, $max] = array_pad(explode('-', $costRange), 2, null);

                // Convert empty strings to null
                $min = $min !== '' ? $min : null;
                $max = $max !== '' ? $max : null;

                // Apply correct filter rules
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

    private function baseTransactionQuery($search = null, $costRange = null)
    {
        return AcknowledgementItem::with(
            'inventoryItems',
            'acknowledgementReceipts.issuedBy.userProfiles', 'issuedBy',
        )
            ->when($search, fn($query, $search) => $query->search($search))
            ->when($costRange, function ($query, $costRange) {
                [$min, $max] = explode('-', $costRange);

                $query->whereHas('inventoryItems', function ($q) use ($min, $max) {
                    if ($min !== '' && $max !== '') {
                        $q->whereBetween('unit_cost', [(float) $min, (float) $max]);
                    } elseif ($min !== '' && $max === '') {
                        $q->where('unit_cost', '>=', (float) $min);
                    } elseif ($min === '' && $max !== '') {
                        $q->where('unit_cost', '<=', (float) $max);
                    }
                });
            });
    }

    public function filterAndPaginateTransaction($search = null, $costRange = null)
    {
        return $this->baseTransactionQuery($search, $costRange)
            ->where('status', 1)
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();
    }

    public function filterAndPaginateTransactionHistory($search = null, $costRange = null)
    {
        return $this->baseTransactionQuery($search, $costRange)
            ->where('status', 0)
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();
    }

    public function createInventoryItems(array $data): void
    {
        foreach ($data['serial_numbers'] as $index => $serialNumber) {
            $propertyNumber = $data['property_number'] . '-' . str_pad($index + 1, 2, '0', STR_PAD_LEFT);

            $inventoryItem = InventoryItem::create([
                'item_classification_id' => $data['item_classification_id'],
                'supplier_id' => $data['supplier_id'],
                'invoice' => $data['invoice'],
                'fund_source' => $data['fund_source'],
                'item_name' => $data['item_name'],
                'description' => $data['description'],
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
            ]);
        }
    }

    public function updateInventoryItem(int $id, array $data): void
    {
        $item = InventoryItem::findOrFail($id);

        $totalAmount = $data['quantity'] * $data['unit_cost'];

        $item->update([
            'item_classification_id' => $data['item_classification_id'],
            'supplier_id' => $data['supplier_id'],
            'invoice' => $data['invoice'],
            'fund_source' => $data['fund_source'],
            'item_name' => $data['item_name'],
            'description' => $data['description'] ?? null,
            'quantity' => $data['quantity'],
            'unit' => $data['unit'],
            'unit_cost' => $data['unit_cost'],
            'total_amount' => $totalAmount,
            'property_number' => $data['property_number'],
            'serial_number' => $data['serial_number'],
            'pr_number' => $data['pr_number'],
            'po_number' => $data['po_number'],
            'remarks' => $data['remarks'] ?? null,
            'date_acquired' => $data['date_acquired'],
            'status' => $data['status'] ?? 1,
        ]);
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
