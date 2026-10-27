<?php

namespace App\Http\Requests;

use App\Models\AssetCondition;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAssetConditionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $condition = $this->route('assetCondition');

        return [
            'condition_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('asset_conditions', 'condition_name')->ignore($condition?->id),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'disposition' => ['required', Rule::in([
                AssetCondition::DISPOSITION_SERVICEABLE,
                AssetCondition::DISPOSITION_FOR_DISPOSAL,
                AssetCondition::DISPOSITION_DISPOSED,
            ])],
        ];
    }

    public function messages(): array
    {
        return [
            'condition_name.required' => 'Condition name is required.',
            'condition_name.unique' => 'This condition name already exists.',
            'condition_name.max' => 'Condition name cannot exceed 255 characters.',
            'description.max' => 'Description cannot exceed 1000 characters.',
            'disposition.required' => 'Choose what this condition means.',
            'disposition.in' => 'Choose one of: serviceable, for disposal, disposed.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'condition_name' => trim((string) $this->condition_name),
        ]);
    }
}

