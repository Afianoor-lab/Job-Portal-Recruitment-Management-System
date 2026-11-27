<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveProfileRequest extends FormRequest
{
    // Access is already checked by the "role:candidate" route middleware.
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'summary' => ['nullable', 'string', 'max:2000'],
            'location' => ['nullable', 'string', 'max:150'],

            'skills' => ['nullable', 'array', 'max:50'],
            'skills.*' => ['required', 'string', 'max:50', 'distinct'],

            'education' => ['nullable', 'array', 'max:20'],
            'education.*' => ['required', 'array:matric,inter,degree,institute,start_year,end_year'],
            'education.*.matric' => [
            'nullable', 'string', 'max:150',
            'required_without_all:education.*.inter,education.*.degree',
            ],
            'education.*.inter' => [
            'nullable', 'string', 'max:150',
            'required_without_all:education.*.matric,education.*.degree',
            ],
            'education.*.degree' => [
            'nullable', 'string', 'max:150',
            'required_without_all:education.*.matric,education.*.inter',
            ],
            'education.*.institute' => ['required', 'string', 'max:150'],
            'education.*.start_year' => ['nullable', 'integer', 'digits:4'],
            'education.*.end_year' => ['nullable', 'integer', 'digits:4', 'gte:education.*.start_year'],

            'experience' => ['nullable', 'array', 'max:30'],
            'experience.*' => ['required', 'array:title,company,start_date,end_date,description'],
            'experience.*.title' => ['required', 'string', 'max:150'],
            'experience.*.company' => ['required', 'string', 'max:150'],
            'experience.*.start_date' => ['required', 'date'],
            'experience.*.end_date' => ['nullable', 'date', 'after_or_equal:experience.*.start_date'],
            'experience.*.description' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
