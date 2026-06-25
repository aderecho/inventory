<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AcknowledgementUploadFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'acknowledgement_id' => ['required', 'integer', 'exists:acknowledgement_receipts,id'],
            'files'              => ['required', 'array', 'min:1'],
            'files.*'            => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ];
    }
}