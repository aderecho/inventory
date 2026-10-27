<?php

namespace App\Http\Requests;

use App\Models\AssetCondition;
use App\Models\InventoryItem;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class DisposeItemsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:inventory_items,id', $this->notOnLoan()],
        ];
    }

    /**
     * Disposing means recording the inspection that says so, which needs a
     * condition flagged as Disposed to exist first.
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $hasDisposedCondition = AssetCondition::ofDisposition(
                    AssetCondition::DISPOSITION_DISPOSED
                )->exists();

                if (! $hasDisposedCondition) {
                    $validator->errors()->add('ids', AssetCondition::MISSING_DISPOSED_MESSAGE);
                }
            },
        ];
    }

    private function notOnLoan(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $onLoan = InventoryItem::whereKey($value)
                ->where('status', InventoryItem::STATUS_BORROWED)
                ->exists();

            if ($onLoan) {
                $fail('An item that is out on loan cannot be disposed of until it is returned.');
            }
        };
    }
}
