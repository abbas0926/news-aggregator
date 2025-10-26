<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserPreferenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        return [
            'preferred_sources' => ['nullable', 'array'],
            'preferred_sources.*' => ['integer', 'exists:sources,id'],
            'preferred_categories' => ['nullable', 'array'],
            'preferred_categories.*' => ['integer', 'exists:categories,id'],
            'preferred_authors' => ['nullable', 'array'],
            'preferred_authors.*' => ['string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'preferred_sources.*.exists' => 'One or more selected sources do not exist.',
            'preferred_categories.*.exists' => 'One or more selected categories do not exist.',
        ];
    }
}
