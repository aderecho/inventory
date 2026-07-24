<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryApiResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'Item' => $this->item_name,
            'Classification' => $this->itemClassification?->classification_name
                ?? $this->itemClassification?->name,
            'Property_number' => $this->property_number,
            'Serial' => $this->serial_number,
            'PR_number' => $this->pr_number,
            'PO_number' => $this->po_number,
            'is_private' => $this->is_private,
            'facility_id' => $this->latestHistoryLocation?->room_id,
        ];
    }
}