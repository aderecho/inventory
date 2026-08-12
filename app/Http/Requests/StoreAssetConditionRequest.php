<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAssetConditionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'condition_name' => ['required', 'string', 'max:255', 'unique:asset_conditions,condition_name'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'condition_name.required' => 'Condition name is required.',
            'condition_name.unique' => 'This condition name already exists.',
            'condition_name.max' => 'Condition name cannot exceed 255 characters.',
            'description.max' => 'Description cannot exceed 1000 characters.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'condition_name' => trim($this->condition_name),
        ]);
    }
}