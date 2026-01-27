<?php

declare(strict_types = 1);

namespace App\Http\Requests\Api\V1\MedicationOffering;

use App\Models\MedicationOffering;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreMedicationOfferingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', MedicationOffering::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'drug_id'    => ['required', 'integer', 'exists:drugs,id'],
            'lot_number' => ['required', 'string', 'max:255', 'regex:/^[A-Z0-9\-]+$/i'],
            'expires_at' => ['required', 'date', 'after:today', 'before:+10 years'],
            'quantity'   => ['required', 'integer', 'min:1', 'max:100000'],
        ];
    }
}
