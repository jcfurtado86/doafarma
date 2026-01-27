<?php

declare(strict_types = 1);

namespace App\Http\Requests\Api\V1\MedicationRequest;

use App\Models\MedicationRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Override;

class StoreMedicationRequestRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', MedicationRequest::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'medication_offering_id' => ['required', 'integer', 'exists:medication_offerings,id'],
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
            'medication_offering_id.required' => 'A oferta de medicamento é obrigatória.',
            'medication_offering_id.integer'  => 'A oferta de medicamento deve ser um número válido.',
            'medication_offering_id.exists'   => 'A oferta de medicamento não existe.',
        ];
    }
}
