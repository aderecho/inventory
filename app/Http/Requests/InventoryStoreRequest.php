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
            'supplier_id' => [
                'required',
                function ($attribute, $value, $fail) {
                    if (is_numeric($value)) {
                        $exists = \App\Models\Supplier::query()
                            ->where('id', $value)
                            ->exists();

                        if (! $exists) {
                            $fail('Selected supplier does not exist.');
                        }
                        return;
                    }

                    $name = trim((string) $value);

                    if ($name === '') {
                        $fail('Supplier name is required.');
                    } elseif (strlen($name) > 180) {
                        $fail('Supplier name must not exceed 180 characters.');
                    }
                },
            ],
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
                    $quantity = max((int) $this->input('quantity', 1), 1);
                    $base = rtrim($value, '-'); // strip any trailing dash the user typed

                    $generated = collect(range(1, $quantity))
                        ->map(fn ($i) => $base . '-' . str_pad($i, 2, '0', STR_PAD_LEFT));

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