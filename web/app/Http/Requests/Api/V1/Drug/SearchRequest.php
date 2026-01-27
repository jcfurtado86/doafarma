<?php

declare(strict_types = 1);

namespace App\Http\Requests\Api\V1\Drug;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SearchRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'q'        => ['nullable', 'string'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'sort'     => ['nullable', 'string', Rule::in(['id', 'product_name', 'substance', 'laboratory'])],
            'order'    => ['nullable', 'string', Rule::in(['asc', 'desc'])],
        ];
    }
}
