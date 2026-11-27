<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UploadCVRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'attachment' => [
            'file',
            'required',
            'max:2048', // 2 MB in kilobytes
            'mimes:pdf,doc,docx',
            'mimetypes:application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'image.max' => 'The attachment may not be larger than 2 MB.',
        ];
    }


}
