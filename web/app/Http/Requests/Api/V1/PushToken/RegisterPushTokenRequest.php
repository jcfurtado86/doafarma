<?php

declare(strict_types = 1);

namespace App\Http\Requests\Api\V1\PushToken;

use Illuminate\Foundation\Http\FormRequest;

class RegisterPushTokenRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'token'       => ['required', 'string', 'max:255'],
            'device_type' => ['nullable', 'string', 'in:ios,android'],
        ];
    }

    /**
     * Get custom error messages for validation rules.
     *
     * @return array<string, string>
     */
    #[\Override]
    public function messages(): array
    {
        return [
            'token.required' => 'O token de notificação é obrigatório.',
            'token.string'   => 'O token deve ser uma string válida.',
            'token.max'      => 'O token não pode ter mais de 255 caracteres.',
            'device_type.in' => 'O tipo de dispositivo deve ser ios ou android.',
        ];
    }
}
