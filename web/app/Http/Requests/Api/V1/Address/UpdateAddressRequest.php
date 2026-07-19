<?php

declare(strict_types = 1);

namespace App\Http\Requests\Api\V1\Address;

use App\Http\Requests\Concerns\CleansNumericInput;
use App\Rules\ValidUF;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAddressRequest extends FormRequest
{
    use CleansNumericInput;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('address'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'label'        => ['sometimes', 'required', 'string', 'max:255'],
            'cep'          => ['sometimes', 'required', 'string', 'size:8'],
            'uf'           => ['sometimes', 'required', 'string', 'size:2', new ValidUF()],
            'city'         => ['sometimes', 'required', 'string', 'max:255'],
            'neighborhood' => ['sometimes', 'required', 'string', 'max:255'],
            'street'       => ['sometimes', 'required', 'string', 'max:255'],
            'number'       => ['sometimes', 'required', 'string', 'max:20'],
            'complement'   => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    #[\Override]
    protected function prepareForValidation(): void
    {
        if ($this->has('cep')) {
            $this->merge([
                'cep' => $this->cleanNumeric($this->input('cep')),
            ]);
        }
    }
}
