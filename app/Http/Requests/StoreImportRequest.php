<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => ['required_without:fileKey', 'file', 'mimes:csv,txt'],
            'fileKey' => ['required_without:file', 'string'],
            'originalFilename' => ['required_with:fileKey', 'string'],
        ];
    }
}


