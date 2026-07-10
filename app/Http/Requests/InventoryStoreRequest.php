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
            'supplier_id' => 'required|integer',
            'room_id' => 'nullable|integer',
            'unit' => 'required|string|max:50',
            'status' => 'required|string|max:50',
            'date_acquired' => 'required|date',
            'quantity' => 'required|integer|min:1',
            'unit_cost' => 'required|numeric',
            'item_name' => 'required|string|max:255',
            'pr_number' => 'required|string|max:50',
            'po_number' => 'required|string|max:50',
            'remarks' => 'nullable|string|max:50',
            'invoice' => 'required|string|max:50',
            'fund_source' => 'required|string|max:50',
            'description' => 'nullable|string',
            'serial_number' => 'nullable|array|min:1',
            'serial_numbers.*' => 'nullable|max:50',
            'is_private' => 'required|boolean',
            'property_number' => [
                'required',
                'string',
                'max:50',
                function ($attribute, $value, $fail) {
                    $exists = \App\Models\InventoryItem::query()
                        ->where('property_number', 'like', $value . '-%')
                        ->whereNull('deleted_at')
                        ->exists();

                    if ($exists) {
                        $fail('The property number base already exists.');
                    }
                },
            ],
        ];
    }
}
