<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBorrowingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'borrower_type' => ['required', 'in:staff,student,outsider'],
            'borrower_name' => ['required', 'string', 'max:255'],
            'borrower_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'employee_number' => ['nullable', 'string', 'max:50'],
            'student_number' => ['nullable', 'string', 'max:50'],
            'organization' => ['nullable', 'string', 'max:255'],
            'contact_number' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:2000'],

            // Who the items were borrowed from: the PUSO staff member.
            'released_by' => ['nullable', 'integer', 'exists:users,id'],
            'borrowed_from_name' => ['nullable', 'string', 'max:255'],

            'borrowed_at' => ['nullable', 'date'],
            'no_due_date' => ['nullable', 'boolean'],
            'due_at' => ['nullable', 'date', 'after_or_equal:borrowed_at'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'integer', 'exists:inventory_items,id'],
            'items.*.quantity' => ['nullable', 'integer', 'min:1'],
            'items.*.condition_out_id' => ['nullable', 'integer', 'exists:asset_conditions,id'],
            'items.*.notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'borrower_type.required' => 'Choose whether the borrower is staff, a student, or an outsider.',
            'borrower_name.required' => 'Enter the borrower\'s name.',
            'items.required' => 'Choose at least one item to lend.',
            'due_at.after_or_equal' => 'The due date cannot be earlier than the borrow date.',
        ];
    }
}

