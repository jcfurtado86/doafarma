<?php

declare(strict_types = 1);

namespace App\Http\Requests\Concerns;

trait CleansNumericInput
{
    /**
     * Clean numeric values, keeping only digits.
     */
    private function cleanNumeric(mixed $value): string
    {
        if (! is_string($value)) {
            return '';
        }

        return preg_replace('/\D/', '', $value) ?? '';
    }
}
