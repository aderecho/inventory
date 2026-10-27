<?php

namespace App\Http\Requests;

use App\Models\AssetCondition;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            // A condition has to declare what it means, so a new wording can
            // never quietly fall outside the disposal process.
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
            'condition_name' => trim($this->condition_name),
        ]);
    }
}
