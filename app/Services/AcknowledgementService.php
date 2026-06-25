<?php

namespace App\Services;

use App\Models\AcknowledgementReceipt;
use App\Models\InventoryItemFile;
use App\Models\AcknowledgementItem;
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
            'acknowledgementItems.files',
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
            'acknowledgementItems.inventoryItems',
            'acknowledgementItems.files.uploadedBy',
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
                            'files'  => $item->files->map(fn($file) => [ // changed from file to files
                                'id'          => $file->id,
                                'file_path'   => $file->file_path,
                                'file_type'   => $file->file_type,
                                'uploaded_by' => $file->uploadedBy?->full_name,
                                'uploaded_at' => $file->created_at->format('M d, Y'),
                            ])->values(),
                        ];
                    })->values(),
                ];
            })->values();

        return [
            'receipt'         => $receipt,
            'groupedByPerson' => $groupedByPerson,
        ];
    }

    public function uploadFile(
        int $acknowledgementId,
        array $files,
        int $uploadBy,
        ?int $accountablePersonId = null
    ): void {
        $query = AcknowledgementItem::where('acknowledgement_id', $acknowledgementId);

        if ($accountablePersonId) {
            $query->where('accountable_person_id', $accountablePersonId);
        }

        $itemIds = $query->pluck('id');

        $alreadyHasFile = InventoryItemFile::whereIn('acknowledgement_item_id', $itemIds)->exists();

        if ($alreadyHasFile) {
            throw new \Exception('This receipt already has uploaded files.');
        }

        $groupId = (string) Str::uuid();

        foreach ($files as $file) {
            $path     = $file->store('acknowledgement_files', 'public');
            $fileType = $file->getClientMimeType();

            foreach ($itemIds as $itemId) {
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
}
