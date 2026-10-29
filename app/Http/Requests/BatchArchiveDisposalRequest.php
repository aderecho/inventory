<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BatchArchiveDisposalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('archive disposal') ?? false;
    }

    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'distinct'],
        ];
    }

    public function messages(): array
    {
        return [
            'ids.required' => 'Select at least one item to archive.',
            'ids.min' => 'Select at least one item to archive.',
        ];
    }
}
