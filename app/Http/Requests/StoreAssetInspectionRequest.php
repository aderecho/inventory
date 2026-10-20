<?php

namespace App\Http\Requests;

use App\Models\AssetCondition;
use App\Models\InventoryItem;
use Illuminate\Foundation\Http\FormRequest;
use Closure;

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
            'selectedItemIds.*' => array_filter([
                'integer',
                'exists:inventory_items,id',
                // Property on loan has to come back before it can be disposed of.
                $this->conditionDisposesItems()
                    ? function (string $attribute, mixed $value, Closure $fail): void {
                        $onLoan = InventoryItem::whereKey($value)
                            ->where('status', InventoryItem::STATUS_BORROWED)
                            ->exists();

                        if ($onLoan) {
                            $fail('An item that is out on loan cannot be disposed of until it is returned.');
                        }
                    }
                    : null,
            ]),
        ];
    }

    private function conditionDisposesItems(): bool
    {
        return AssetCondition::whereKey($this->input('asset_condition_id'))
            ->where('disposition', AssetCondition::DISPOSITION_DISPOSED)
            ->exists();
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
