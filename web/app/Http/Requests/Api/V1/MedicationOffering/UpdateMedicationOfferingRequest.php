<?php

declare(strict_types = 1);

namespace App\Http\Requests\Api\V1\MedicationOffering;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMedicationOfferingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('medicationOffering'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'lot_number' => ['sometimes', 'required', 'string', 'max:255', 'regex:/^[A-Z0-9\-]+$/i'],
            'expires_at' => ['sometimes', 'required', 'date', 'after:today', 'before:+10 years'],
            'quantity'   => ['sometimes', 'required', 'integer', 'min:1', 'max:100000'],
        ];
    }
}
