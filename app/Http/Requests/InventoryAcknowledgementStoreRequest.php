<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InventoryAcknowledgementStoreRequest extends FormRequest
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
            'inventory_item_id' => 'required|array',
            'inventory_item_id.*' => 'required|exists:inventory_items,id',

            'accountable_persons_id' => 'required|exists:users,id',
            'issued_by_id' => 'required|exists:users,id',
            'room_id' => 'required|integer',
            'category' => 'required',
            'created_by' => 'required|exists:users,id',
            'par_date' => 'required|date',
            'remarks' => 'nullable|string',
            'reason_for_transfer' => 'nullable|string|max:1000',

            'transfer_type' => 'nullable|array',
            'transfer_type.*' => 'integer|exists:transfer_types,id',
            'transfer_type_other' => 'nullable|string|max:255',
            'approved_by' => 'nullable|integer|exists:user_profiles,id',
        ];
    }
}
