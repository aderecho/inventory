<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AcknowledgementUploadFileRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5048',

            'acknowledgement_item_ids' => 'required|array|min:1',

            'acknowledgement_item_ids.*' => [
                'exists:acknowledgement_items,id',
            ],
        ];
    }
}