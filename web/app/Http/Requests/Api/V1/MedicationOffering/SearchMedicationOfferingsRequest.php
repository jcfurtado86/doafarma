<?php

declare(strict_types = 1);

namespace App\Http\Requests\Api\V1\MedicationOffering;

use App\Enums\UserRole;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Override;

class SearchMedicationOfferingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->role === UserRole::Receptor;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'min:1'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    #[Override]
    public function messages(): array
    {
        return [
            'q.string' => 'O campo de busca deve ser um texto.',
            'q.min'    => 'O campo de busca deve ter pelo menos :min caractere.',
        ];
    }
}
