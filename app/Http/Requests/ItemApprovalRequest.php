<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ItemApprovalRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Only the item's accountable person may answer; the controller checks that.
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'approval_remarks' => filled($this->approval_remarks) ? trim($this->approval_remarks) : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'approval_status' => ['required', Rule::in(['pending', 'approved', 'rejected'])],
            'approval_remarks' => ['nullable', 'required_if:approval_status,rejected', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'approval_remarks.required_if' => 'Please tell us why you are rejecting this item.',
            'approval_remarks.max' => 'Keep the remarks under 1,000 characters.',
        ];
    }

    /** Remarks only stay with a rejection; approving or reopening clears them. */
    public function approvalRemarks(): ?string
    {
        return $this->validated('approval_status') === 'rejected'
            ? $this->validated('approval_remarks')
            : null;
    }
}
