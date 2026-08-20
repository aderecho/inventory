<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InventoryStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'item_classification_id' => 'required|integer',
            'supplier_id' => 'required|integer|exists:suppliers,id',
            'room_id' => 'nullable|integer',
            'unit' => 'required|string|max:50',
            'date_acquired' => 'required|date',
            'quantity' => 'required|integer|min:1',
            'unit_cost' => 'required|numeric',
            'item_name' => 'required|string|max:255',
            'brand' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'pr_number' => 'required|string|max:50',
            'po_number' => 'required|string|max:50',
            'remarks' => 'nullable|string|max:50',
            'invoice' => 'required|string|max:50',
            'fund_source' => 'required|string|max:50',
            'description' => 'nullable|string',
            'descriptions' => 'nullable|array',
            'descriptions.*' => 'nullable|string',
            'serial_numbers' => 'nullable|array|min:1',
            'serial_numbers.*' => 'nullable|max:50',
            'is_private' => 'required|boolean',
            'asset_condition_id' => 'required|integer|exists:asset_conditions,id',
            'property_number' => [
                'required',
                'string',
                'max:50',
                function ($attribute, $value, $fail) {
                    $quantity = max((int) $this->input('quantity', 1), 1);
                    $base = rtrim($value, '-'); // strip any trailing dash the user typed

                    $generated = collect(range(1, $quantity))
                        ->map(fn($i) => $base . '-' . str_pad($i, 3, '0', STR_PAD_LEFT));

                    $conflict = \App\Models\InventoryItem::query()
                        ->whereIn('property_number', $generated)
                        ->whereNull('deleted_at')
                        ->exists();

                    if ($conflict) {
                        $fail('One or more property numbers in this batch already exist.');
                    }
                },
            ],
        ];
    }
}
