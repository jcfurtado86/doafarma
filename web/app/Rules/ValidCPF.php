<?php

declare(strict_types = 1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Rule to validate Brazilian CPF (Cadastro de Pessoas Físicas).
 *
 * This rule validates the CPF using the official algorithm that checks
 * the two verification digits at the end of the CPF number.
 */
class ValidCPF implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail("O campo {$attribute} deve ser uma string.");

            return;
        }

        // Remove any non-numeric characters
        $cpf = preg_replace('/\D/', '', $value);

        if ($cpf === null || mb_strlen($cpf) !== 11) {
            $fail("O campo {$attribute} deve conter 11 dígitos.");

            return;
        }

        // Check for CPFs with all same digits (e.g., 111.111.111-11)
        if (preg_match('/^(\d)\1{10}$/', $cpf)) {
            $fail("O campo {$attribute} é inválido.");

            return;
        }

        // Validate first check digit
        if (! $this->validateCheckDigit($cpf, 9)) {
            $fail("O campo {$attribute} é inválido.");

            return;
        }

        // Validate second check digit
        if (! $this->validateCheckDigit($cpf, 10)) {
            $fail("O campo {$attribute} é inválido.");

            return;
        }
    }

    /**
     * Validate a check digit of the CPF.
     *
     * @param  string  $cpf  The CPF number (only digits)
     * @param  int  $position  The position of the check digit (9 or 10)
     */
    private function validateCheckDigit(string $cpf, int $position): bool
    {
        $sum        = 0;
        $multiplier = $position + 1;

        for ($i = 0; $i < $position; $i++) {
            $sum += (int) $cpf[$i] * $multiplier;
            $multiplier--;
        }

        $remainder  = $sum % 11;
        $checkDigit = $remainder < 2 ? 0 : 11 - $remainder;

        return (int) $cpf[$position] === $checkDigit;
    }
}
