<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAssetInspectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'asset_condition_id' => ['required', 'integer', 'exists:asset_conditions,id'],
            'inspection_date' => ['required', 'date'],
            'remarks' => ['nullable', 'string', 'max:1000'],
            'selectedItemIds' => ['required', 'array', 'min:1'],
            'selectedItemIds.*' => ['integer', 'exists:inventory_items,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'asset_condition_id.required' => 'Asset condition is required.',
            'asset_condition_id.exists' => 'Selected asset condition does not exist.',
            'inspection_date.required' => 'Inspection date is required.',
            'inspection_date.date' => 'Inspection date must be a valid date.',
            'selectedItemIds.required' => 'At least one item must be selected.',
            'selectedItemIds.min' => 'At least one item must be selected.',
            'selectedItemIds.*.exists' => 'One or more selected items do not exist.',
        ];
    }
}