<?php

namespace App\Http\Controllers;

use App\Exceptions\InventoryImportException;
use App\Http\Controllers\Concerns\StreamsProgress;
use App\Http\Requests\InventoryAcknowledgementStoreRequest;
use App\Http\Requests\DisposeItemsRequest;
use App\Http\Requests\InventoryImportRequest;
use App\Http\Requests\InventoryStoreRequest;
use App\Http\Requests\InventoryUpdateRequest;
use App\Http\Requests\UpdateInventoryCategoryRequest;
use App\Http\Resources\InventoryApiResource;
use App\Models\AcknowledgementReceipt;
use App\Models\InventoryItem;
use App\Models\ItemClassification;
use App\Models\Supplier;
use App\Models\AssetCondition;
use App\Models\TransferType;
use App\Models\User;
use App\Services\Api\InventoryApiService;
use App\Services\DownloadPngService;
use App\Services\DownloadPdfService;
use App\Services\InventoryExportService;
use App\Services\InventoryImportService;
use App\Services\InventoryService;
use App\Services\RoomApiService;
use Illuminate\Http\Request;


class InventoryController extends Controller
{
    use StreamsProgress;

    public function __construct(
        protected InventoryService $inventoryService,
        protected InventoryApiService $inventoryApiService,
    ) {}

    public function InventoryItems(Request $request, RoomApiService $roomsApi)
    {
        $roomResult = $roomsApi->fetchRooms();

        $roomsLookup = collect($roomResult['data'])
            ->keyBy('id');

        $rooms = collect($roomResult['data'])
            ->map(function ($room) {
                return [
                    'id' => $room['id'],
                    'room_name' => $room['room_name'],
                    'room_code' => $room['room_code'],
                    'description' => $room['description'],
                    'building' => $room['building'],
                    'building_name' => $room['building_name'],
                    'capacity' => $room['capacity'],
                ];
            })
            ->values();

        $search = $request->input('search');
        $costRange = $request->input('cost_range');
        $assetConditionId = $request->input('asset_condition_id');
        $acknowledgementStatus = $request->input('acknowledgement_status');
        $roomId = $request->input('room_id');

        $itemClassifications = ItemClassification::all();
        $suppliers = Supplier::all();
        $assetConditions = AssetCondition::all();
        $transferTypes = TransferType::orderBy('name')->get(['id', 'name']);
        $biggestCategories = $this->biggestCategoriesByClassification();
        $users = User::with('userProfiles')->get();
        $adminProfiles = $this->inventoryService->getAccountableUserProfiles();

        $items = $this->inventoryService->filterAndPaginateInventory(
            $search,
            $costRange,
            $assetConditionId,
            $acknowledgementStatus,
            $roomId,
        );

        $items->getCollection()->transform(function ($item) use ($roomsLookup) {

            $roomId = $item->latestHistoryLocation?->room_id;

            $item->room_name = isset($roomsLookup[$roomId])
                ? $roomsLookup[$roomId]['room_name']
                : 'N/A';

            $item->room_id = $roomId;

            return $item;
        });

        return inertia('Inventory/InventoryItem', [
            'rooms' => $rooms,
            'items' => $items,
            'itemClassifications' => $itemClassifications,
            'suppliers' => $suppliers,
            'users' => $users,
            'adminProfiles' => $adminProfiles,
            'assetConditions' => $assetConditions,
            'transferTypes' => $transferTypes,
            'biggestCategories' => $biggestCategories,
        ]);
    }

    /**
     * Highest PAR/ICS number per classification, e.g. ['223' => '223-2026-10-572'].
     */
    protected function biggestCategoriesByClassification(): array
    {
        $biggest = AcknowledgementReceipt::query()
            ->whereNotNull('category')
            ->pluck('category')
            ->reduce(function (array $carry, string $category) {
                $parts = explode('-', $category);
                $classification = $parts[0] ?? null;

                if (! $classification) {
                    return $carry;
                }

                $lastNumber = (int) end($parts);
                $current = $carry[$classification] ?? null;

                if ($current === null || $lastNumber > $current['number']) {
                    $carry[$classification] = [
                        'number'   => $lastNumber,
                        'category' => $category,
                    ];
                }

                return $carry;
            }, []);

        return collect($biggest)
            ->map(fn ($entry) => $entry['category'])
            ->all();
    }

    public function apiIndex()
    {
        $items = $this->inventoryApiService->getInventory();

        return InventoryApiResource::collection($items);
    }

    public function apiShow($id)
    {
        return new InventoryApiResource(
            $this->inventoryApiService->getById($id)
        );
    }

    public function InventoryAcknowledgementsStore(InventoryAcknowledgementStoreRequest $request)
    {
        $this->inventoryService->createAcknowledgements($request->validated());

        return redirect()->route('inventory.items')->with('success', 'Items assigned successfully!');
    }

    public function updateCategoryForItems(UpdateInventoryCategoryRequest $request)
    {
        $this->inventoryService->updateCategory(
            $request->input('inventory_item_ids'),
            $request->input('category')
        );

        return back()->with('success', 'Category updated for selected items.');
    }


    public function store(InventoryStoreRequest $request)
    {
        $this->inventoryService->createinventoryItems($request->validated());

        return redirect()->route('inventory.items');
    }


    public function update(InventoryUpdateRequest $request, $id)
    {
        $this->inventoryService->updateInventoryItem($id, $request->validated());

        return redirect()->route('inventory.items');
    }

    public function downloadQrPngs(Request $request, DownloadPngService $downloadPngService)
    {
        $ids = $request->input('ids', []);

        if (count($ids) === 1) {
            $pngPath = $downloadPngService->generateQrPng($ids[0]);

            return response()
                ->download($pngPath)
                ->deleteFileAfterSend(true);
        }

        // Multiple items — return ZIP
        $zipPath = $downloadPngService->generateQrZip($ids);

        return response()
            ->download($zipPath)
            ->deleteFileAfterSend(true);
    }

    /**
    * Downloads the fixed three-row sample inventory workbook.
    */
    public function exportSample(InventoryExportService $exporter)
    {
        return response()
            ->download($exporter->sampleToFile(), 'Inventory_Export_Sample.xlsx', [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])
            ->deleteFileAfterSend(true);
    }

    /**
     * Exports every item in the database using the sample's columns.
     */
    public function exportExcel(Request $request, InventoryExportService $exporter)
    {
        $format = $request->query('format') === 'csv' ? 'csv' : 'xlsx';

        $contentType = $format === 'csv'
            ? 'text/csv'
            : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

        return response()
            ->download(
                $exporter->exportToFile($format),
                'Inventory_Items_' . now()->format('Y-m-d_His') . '.' . $format,
                ['Content-Type' => $contentType],
            )
            ->deleteFileAfterSend(true);
    }

    public function downloadQrPdfs(Request $request, DownloadPdfService $downloadPdfService)
    {
        $ids = $request->input('ids', []);

        $pdfPath = $downloadPdfService->generateQrPdf($ids);

        return response()
            ->download($pdfPath)
            ->deleteFileAfterSend(true);
    }

    /**
    * Progress stream for the stickers: the same protocol the IIRUP print uses,
    * so both report through the same card in the browser.
    */
    public function streamQrPngs(Request $request, DownloadPngService $downloadPngService)
    {
        return $this->streamProgress(
            $request,
            ['ids' => 'required|array|min:1', 'ids.*' => 'integer'],
            function (array $data, ?callable $onProgress) use ($downloadPngService) {
                $ids = array_values($data['ids']);

                if (count($ids) === 1) {
                    $path = $downloadPngService->generateQrPng((int) $ids[0], $onProgress);

                    return ['filename' => 'qr-code.png', 'contents' => $this->readThenRemove($path)];
                }

                $path = $downloadPngService->generateQrZip($ids, $onProgress);

                return ['filename' => 'qr-codes.zip', 'contents' => $this->readThenRemove($path)];
            },
        );
    }

    public function streamQrPdfs(Request $request, DownloadPdfService $downloadPdfService)
    {
        return $this->streamProgress(
            $request,
            ['ids' => 'required|array|min:1', 'ids.*' => 'integer'],
            function (array $data, ?callable $onProgress) use ($downloadPdfService) {
                $path = $downloadPdfService->generateQrPdf(array_values($data['ids']), $onProgress);

                return ['filename' => 'qr-code.pdf', 'contents' => $this->readThenRemove($path)];
            },
        );
    }

    /**
     * The streamed response carries the bytes itself, so the temporary file the
     * generator wrote is read once and cleaned up here.
     */
    private function readThenRemove(string $path): string
    {
        $contents = file_get_contents($path);

        if ($contents === false) {
            abort(500, 'The generated file could not be read.');
        }

        @unlink($path);

        return $contents;
    }

    public function destroy($id)
    {
        $item = InventoryItem::findOrFail($id);
        $item->delete();

        return redirect()->back()->with('success', 'Item deleted successfully.');
    }

    /**
     * Archives (soft deletes) every selected item, one at a time so each keeps
     * its model events and activity log entry.
     */
    public function batchArchive(Request $request)
    {
        $data = $request->validate([
            'ids'   => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $items = InventoryItem::whereIn('id', $data['ids'])->get();

        foreach ($items as $item) {
            $item->delete();
        }

        return back()->with(
            'success',
            $items->count() === 1
                ? 'Item archived successfully.'
                : $items->count() . ' items archived successfully.'
        );
    }

    /**
     * Marks the ticked items as disposed in one step. The item status follows
     * the inspection this records, so nothing has to be set by hand.
     */
    public function disposeItems(DisposeItemsRequest $request)
    {
        $count = $this->inventoryService->disposeItems(
            $request->validated('ids'),
            auth()->id(),
        );

        return back()->with(
            'success',
            $count === 1
                ? 'Item marked as disposed.'
                : $count . ' items marked as disposed.'
        );
    }

    public function convert(Request $request)
    {
        return $this->inventoryService->convertToCsv($request);
    }

    public function importCsv(Request $request)
    {
        return $this->inventoryService->importCsv($request);
    }

    /**
     * Imports the PAR/ICS spreadsheet (CSV or Excel) and returns a per-row report.
     */
    public function import(InventoryImportRequest $request, InventoryImportService $importer)
    {
        try {
            $result = $importer->import(
                file: $request->file('file'),
                dryRun: $request->boolean('dry_run'),
            );
        } catch (InventoryImportException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($result);
    }

    public function exportCsv()
    {
        return $this->inventoryService->exportCsv();
    }
}
