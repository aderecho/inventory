<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InventoryUpdateRequest extends FormRequest
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
            'invoice' => 'required|string|max:50',
            'fund_source' => 'required|string|max:50',
            'item_name' => 'required|string|max:255',
            'brand' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'quantity' => 'required|integer',
            'unit' => 'required|string|max:50',
            'unit_cost' => 'required|numeric',
            'total_amount' => 'nullable|numeric',
            'serial_number' => 'nullable|max:50',
            'pr_number' => 'required|string|max:50',
            'po_number' => 'required|string|max:50',
            'remarks' => 'nullable|string|max:50',
            'date_acquired' => 'required|date',
            'status' => 'nullable|integer',
            'is_private' => 'required|boolean',
            'property_number' => [
                'required',
                Rule::unique('inventory_items', 'property_number')
                    ->ignore($this->route('id'))
                    ->whereNull('deleted_at'),
            ],
        ];
    }
}
