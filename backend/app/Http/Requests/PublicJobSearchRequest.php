<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class PublicJobSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:255'],
            'keyword' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'category_id' => ['nullable', 'integer', 'exists:job_categories,id'],
            'job_type' => ['nullable', 'in:full_time,part_time,contract,temporary,internship'],
            'salary_min' => ['nullable', 'integer', 'min:0'],
            'salary_max' => ['nullable', 'integer', 'min:0'],
            'posted_after' => ['nullable', 'date'],
            'posted_before' => ['nullable', 'date', 'after_or_equal:posted_after'],
            'sort' => ['nullable', 'in:newest,salary'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $minimum = $this->input('salary_min');
                $maximum = $this->input('salary_max');

                if ($minimum !== null && $maximum !== null && (int) $maximum < (int) $minimum) {
                    $validator->errors()->add('salary_max', 'The maximum salary must be at least the minimum salary.');
                }
            },
        ];
    }
}
