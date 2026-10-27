<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CategoryImportRequest extends FormRequest
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
            'file.mimes'    => 'Only .xlsx, .xls, or .csv files can be imported.',
            'file.max'      => 'The file is larger than 20 MB.',
        ];
    }
}
