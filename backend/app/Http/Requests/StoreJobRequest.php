<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreJobRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:50000'],
            'requirements' => ['nullable', 'string', 'max:50000'],
            'category_id' => ['required', 'integer', 'exists:job_categories,id'],
            'job_type' => ['required', 'in:full_time,part_time,contract,temporary,internship'],
            'location' => ['required', 'string', 'max:255'],
            'salary_min' => ['nullable', 'integer', 'min:0', 'max:99999999'],
            'salary_max' => ['nullable', 'integer', 'min:0', 'max:99999999'],
            'deadline' => ['nullable', 'date', 'after_or_equal:today'],
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
