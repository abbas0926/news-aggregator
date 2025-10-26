<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ArticleSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; 
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'source' => ['nullable', 'string', 'exists:sources,slug'],
            'category' => ['nullable', 'string', 'exists:categories,slug'],
            'author' => ['nullable', 'string', 'max:255'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'date_to.after_or_equal' => 'The end date must be equal to or after the start date.',
            'per_page.max' => 'The maximum number of items per page is 100.',
        ];
    }
}
