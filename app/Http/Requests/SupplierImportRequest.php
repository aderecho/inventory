<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SupplierImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => 'required|file|mimes:xlsx,xls,csv,txt|max:20480',
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Please choose a CSV or Excel file to import.',
            // PHP drops a file above its upload_max_filesize before Laravel sees it.
            'file.uploaded' => 'The file did not reach the server. It may be larger than the server upload limit.',
            'file.mimes'    => 'Only .xlsx, .xls, or .csv files can be imported.',
            'file.max'      => 'The file is larger than 20 MB.',
        ];
    }
}
