<?php

namespace App\Http\Requests;

class UpdateJobRequest extends StoreJobRequest
{
    public function rules(): array
    {
        return array_map(
            static fn (array $rules): array => array_values(array_filter(
                $rules,
                static fn (mixed $rule): bool => $rule !== 'required',
            )),
            parent::rules(),
        );
    }
}
