<?php

declare(strict_types = 1);

namespace App\Rules;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * Validates that a CPF is unique by checking its hash.
 *
 * Since CPF is encrypted, we can't use a normal unique rule.
 * This rule hashes the CPF and checks against the cpf_hash column.
 */
class UniqueCpfHash implements ValidationRule
{
    /**
     * Create a new rule instance.
     *
     * @param  int|null  $ignoreId  User ID to ignore (for updates)
     */
    public function __construct(
        private readonly ?int $ignoreId = null
    ) {
    }

    /**
     * Run the validation rule.
     *
     * @param Closure(string, ?string=):PotentiallyTranslatedString $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            return;
        }

        $hash  = hash('sha256', $value);
        $query = User::where('cpf_hash', $hash);

        if ($this->ignoreId !== null) {
            $query->where('id', '!=', $this->ignoreId);
        }

        if ($query->exists()) {
            $fail('Este CPF já está cadastrado.');
        }
    }
}
