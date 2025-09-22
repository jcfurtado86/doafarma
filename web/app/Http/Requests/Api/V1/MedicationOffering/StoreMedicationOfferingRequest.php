<?php

declare(strict_types = 1);

namespace App\Http\Requests\Api\V1\MedicationOffering;

use Illuminate\Foundation\Http\FormRequest;

class StoreMedicationOfferingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->doctor()->exists();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'drug_id'    => ['required', 'integer', 'exists:drugs,id'],
            'lot_number' => ['required', 'string', 'max:255'],
            'expires_at' => ['required', 'date', 'after:today'],
            'quantity'   => ['required', 'integer', 'min:1'],
        ];
    }
}
