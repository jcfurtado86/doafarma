<?php

declare(strict_types = 1);

namespace App\Http\Requests\Auth;

use App\Models\User;
use App\Rules\ValidCPF;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules;

class ReceptorRegistrationRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name'           => ['required', 'string', 'max:255'],
            'email'          => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'cpf'            => ['required', 'string', new ValidCPF(), 'unique:' . User::class . ',cpf'],
            'phone_number'   => ['required', 'string', 'min:10', 'max:11', 'unique:' . User::class . ',phone_number'],
            'password'       => ['required', 'confirmed', Rules\Password::defaults()],
            'device_name'    => ['required', 'string', 'max:255'],
            'terms_accepted' => ['required', 'accepted'],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'email'        => is_string($email = $this->input('email')) ? mb_strtolower($email) : '',
            'phone_number' => $this->cleanNumeric($this->input('phone_number')),
            'cpf'          => $this->cleanNumeric($this->input('cpf')),
        ]);
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    #[\Override]
    public function messages(): array
    {
        return [
            'name.required'           => 'O nome é obrigatório.',
            'name.max'                => 'O nome não pode ter mais de 255 caracteres.',
            'email.required'          => 'O e-mail é obrigatório.',
            'email.email'             => 'O e-mail deve ser um endereço de e-mail válido.',
            'email.unique'            => 'Este e-mail já está cadastrado.',
            'cpf.required'            => 'O CPF é obrigatório.',
            'cpf.unique'              => 'Este CPF já está cadastrado.',
            'phone_number.required'   => 'O telefone é obrigatório.',
            'phone_number.min'        => 'O telefone deve ter pelo menos 10 dígitos.',
            'phone_number.max'        => 'O telefone deve ter no máximo 11 dígitos.',
            'phone_number.unique'     => 'Este telefone já está cadastrado.',
            'password.required'       => 'A senha é obrigatória.',
            'password.confirmed'      => 'A confirmação de senha não confere.',
            'device_name.required'    => 'O nome do dispositivo é obrigatório.',
            'terms_accepted.required' => 'Você deve aceitar os termos de uso.',
            'terms_accepted.accepted' => 'Você deve aceitar os termos de uso.',
        ];
    }

    /**
     * Clean numeric values (remove non-numeric characters).
     */
    private function cleanNumeric(mixed $value): string
    {
        if (! is_string($value)) {
            return '';
        }

        return preg_replace('/\D/', '', $value) ?? '';
    }
}
