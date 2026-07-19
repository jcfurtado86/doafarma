<?php

declare(strict_types = 1);

namespace App\Http\Requests\Api\V1\Address;

use App\Http\Requests\Concerns\CleansNumericInput;
use App\Models\Address;
use App\Rules\ValidUF;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreAddressRequest extends FormRequest
{
    use CleansNumericInput;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Address::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'label'        => ['required', 'string', 'max:255'],
            'cep'          => ['required', 'string', 'size:8'],
            'uf'           => ['required', 'string', 'size:2', new ValidUF()],
            'city'         => ['required', 'string', 'max:255'],
            'neighborhood' => ['required', 'string', 'max:255'],
            'street'       => ['required', 'string', 'max:255'],
            'number'       => ['required', 'string', 'max:20'],
            'complement'   => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    #[\Override]
    protected function prepareForValidation(): void
    {
        $this->merge([
            'cep' => $this->cleanNumeric($this->input('cep')),
        ]);
    }
}
