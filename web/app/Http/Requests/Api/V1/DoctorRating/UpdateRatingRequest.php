<?php

declare(strict_types = 1);

namespace App\Http\Requests\Api\V1\DoctorRating;

use App\Models\DoctorRating;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Override;

class UpdateRatingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $rating = $this->route('doctorRating');

        if (! $rating instanceof DoctorRating) {
            return false;
        }

        return $this->user()->can('update', $rating);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'rating'  => ['sometimes', 'required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
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
            'rating.required' => 'A avaliação é obrigatória.',
            'rating.integer'  => 'A avaliação deve ser um número inteiro.',
            'rating.min'      => 'A avaliação deve ser no mínimo 1 estrela.',
            'rating.max'      => 'A avaliação deve ser no máximo 5 estrelas.',
            'comment.string'  => 'O comentário deve ser um texto.',
            'comment.max'     => 'O comentário deve ter no máximo 1000 caracteres.',
        ];
    }
}
