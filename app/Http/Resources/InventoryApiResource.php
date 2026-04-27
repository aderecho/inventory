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
            // 'Classification' => $this->itemClassification->classification_name,
            'Classification' => $this->itemClassification?->classification_name
                ?? $this->itemClassification?->name,
            'Property Number' => $this->property_number,
            'Description' => $this->description,
            'Serial' => $this->serial_number,
            'PR Number' => $this->pr_number,
            'PO Number' => $this->po_number,

            // FIXED RELATION ACCESS
            'Supplier' => $this->supplier->supplier_name,
            

            // CLEAN DISPLAY FORMAT (what you want visually)
            // 'display_name' =>
            //     $this->item_name .
            //     ' (' . $this->property_number . ') - ' .
            //     ($this->supplier?->supplier_name ?? 'No Supplier') .
            //     ' (' . ($this->itemClassification?->name ?? 'No Classification') . ')',
        ];
    }
}