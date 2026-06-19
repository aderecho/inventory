<?php

namespace App\Services;

use App\Models\AcknowledgementReceipt;
use App\Models\InventoryItemFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class AcknowledgementService
{

    public function getPaginatedReceipts(?string $search = null, int $perPage = 10)
    {
        return AcknowledgementReceipt::with([
            'issuedBy.userProfiles',
            'acknowledgementItems.inventoryItems',
            'acknowledgementItems.accountablePerson',
            'acknowledgementItems.file',
        ])
            ->when($search, fn($query, $search) => $query->where('category', 'like', "%{$search}%"))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }
    public function getReceiptWithGroupedPersons(int $id): array
    {
        $receipt = AcknowledgementReceipt::with([
            'acknowledgementItems.accountablePerson',
            'acknowledgementItems.issuedBy',
            'acknowledgementItems.inventoryItems', // ← your model uses inventoryItems not inventoryItem
            'acknowledgementItems.file.uploadedBy',
        ])->findOrFail($id);

        $groupedByPerson = $receipt->acknowledgementItems
            ->groupBy('accountable_person_id')
            ->map(function ($items) {
                $person = $items->first()->accountablePerson;

                return [
                    'person' => [
                        'id'   => $person->id,
                        'name' => $person->full_name,
                    ],
                    'items' => $items->map(function ($item) {
                        return [
                            'id'             => $item->id,
                            'inventory_item' => [
                                'id'              => $item->inventoryItems->id,
                                'item_name'       => $item->inventoryItems->item_name,
                                'property_number' => $item->inventoryItems->property_number,
                            ],
                            'status' => $item->status,
                            'file'   => $item->file ? [
                                'id'            => $item->file->id,
                                'file_path'     => $item->file->file_path,
                                'file_type'     => $item->file->file_type,
                                'file_group_id' => $item->file->file_group_id,
                                'uploaded_by'   => $item->file->uploadedBy?->name,
                                'uploaded_at'   => $item->file->created_at->format('M d, Y'),
                            ] : null,
                        ];
                    })->values(),
                ];
            })->values();

        return [
            'receipt'          => $receipt,
            'groupedByPerson'  => $groupedByPerson,
        ];
    }

    public function uploadFile(
        array $acknowledgementItemIds,
        UploadedFile $file,
        int $uploadBy
    ): void {
        $path      = $file->store('acknowledgement_files', 'public');
        $fileType  = $file->getClientMimeType();
        $groupId   = (string) Str::uuid();

        foreach ($acknowledgementItemIds as $itemId) {
            InventoryItemFile::create([
                'acknowledgement_item_id' => $itemId,
                'file_group_id'           => $groupId,
                'file_path'               => $path,
                'file_type'               => $fileType,
                'upload_by'               => $uploadBy,
            ]);
        }
    }
}
