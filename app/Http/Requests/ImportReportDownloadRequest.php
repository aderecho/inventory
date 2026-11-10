<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportReportDownloadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'report' => 'required|string|max:2097152',
            'format' => 'required|in:csv,xlsx',
            'filename' => 'nullable|string|max:100',
        ];
    }
}
