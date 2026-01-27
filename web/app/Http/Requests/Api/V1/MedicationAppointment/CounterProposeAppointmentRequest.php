<?php

declare(strict_types = 1);

namespace App\Http\Requests\Api\V1\MedicationAppointment;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Override;

class CounterProposeAppointmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Authorization is handled by the controller via policy.
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
            'scheduled_date' => ['required', 'date', 'after_or_equal:today'],
            'scheduled_time' => ['required', 'date_format:H:i'],
            'address_id'     => ['sometimes', 'nullable', 'integer', 'exists:addresses,id'],
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
            'scheduled_date.required'       => 'A data é obrigatória.',
            'scheduled_date.date'           => 'A data deve ser uma data válida.',
            'scheduled_date.after_or_equal' => 'A data deve ser hoje ou no futuro.',
            'scheduled_time.required'       => 'O horário é obrigatório.',
            'scheduled_time.date_format'    => 'O horário deve estar no formato HH:MM.',
            'address_id.integer'            => 'O endereço deve ser um número válido.',
            'address_id.exists'             => 'O endereço selecionado não existe.',
        ];
    }
}
