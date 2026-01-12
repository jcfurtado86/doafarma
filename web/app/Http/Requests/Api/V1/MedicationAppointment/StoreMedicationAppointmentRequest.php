<?php

declare(strict_types = 1);

namespace App\Http\Requests\Api\V1\MedicationAppointment;

use App\Models\MedicationAppointment;
use App\Models\MedicationRequest;
use Illuminate\Foundation\Http\FormRequest;

class StoreMedicationAppointmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $requestId = $this->input('medication_request_id');

        if (! is_numeric($requestId)) {
            return true; // Let validation handle it
        }

        $medicationRequest = MedicationRequest::find((int) $requestId);

        if ($medicationRequest === null) {
            return true; // Let validation handle it
        }

        return $this->user()->can('create', [MedicationAppointment::class, $medicationRequest]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'medication_request_id' => ['required', 'integer', 'exists:medication_requests,id'],
            'scheduled_date'        => ['required', 'date', 'after_or_equal:today'],
            'scheduled_time'        => ['required', 'date_format:H:i'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    #[\Override]
    public function messages(): array
    {
        return [
            'medication_request_id.required' => 'A solicitação de medicamento é obrigatória.',
            'medication_request_id.integer'  => 'A solicitação de medicamento deve ser um número válido.',
            'medication_request_id.exists'   => 'A solicitação de medicamento não existe.',
            'scheduled_date.required'        => 'A data do agendamento é obrigatória.',
            'scheduled_date.date'            => 'A data do agendamento deve ser uma data válida.',
            'scheduled_date.after_or_equal'  => 'A data deve ser hoje ou no futuro.',
            'scheduled_time.required'        => 'O horário do agendamento é obrigatório.',
            'scheduled_time.date_format'     => 'O horário deve estar no formato HH:MM.',
        ];
    }
}
