<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Support\PropertyNumber;

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
            'item_classification_id' => [
                'nullable',
                'integer',
                'exists:item_classifications,id',
                Rule::requiredIf(fn () => ! $this->boolean('free_text_property_number')),
            ],
            'free_text_property_number' => 'nullable|boolean',
            'supplier_id' => 'required|integer|exists:suppliers,id',
            'room_id' => 'nullable|integer',
            'unit' => 'required|string|max:50',
            'date_acquired' => 'required|date',
            'lifespan' => 'required|integer|min:1',
            'quantity' => 'required|integer|min:1',
            'unit_cost' => 'required|numeric',
            'item_name' => 'required|string|max:255',
            'brand' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'pr_number' => 'required|string|max:50',
            'po_number' => 'required|string|max:50',
            'po_number_date' => 'required|date',  
            'remarks' => 'nullable|string|max:50',
            'invoice' => 'required|string|max:50',
            'sales_invoice_date' => 'required|date', 
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
                'max:60',
                function ($attribute, $value, $fail) {
                    $quantity = max((int) $this->input('quantity', 1), 1);
                    $generated = PropertyNumber::forQuantity($value, $quantity);

                    $conflict = \App\Models\InventoryItem::query()
                        ->whereIn('property_number', $generated)
                        ->whereNull('deleted_at')
                        ->exists();

                    if ($conflict) {
                        $fail('One or more property numbers in this batch already exist.');
                    }
                },
            ],
            'old_property_number' => 'nullable|string|max:255',
        ];
    }
}
