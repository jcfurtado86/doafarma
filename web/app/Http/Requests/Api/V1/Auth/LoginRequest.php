<?php

declare(strict_types = 1);

namespace App\Http\Requests\Api\V1\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
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
            'email'    => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
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
            'email.required'    => 'The email field is required.',
            'email.email'       => 'The email must be a valid email address.',
            'password.required' => 'The password field is required.',
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException|ThrottleRequestsException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (! Auth::attempt($this->only('email', 'password'))) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => 'These credentials do not match our records.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ThrottleRequestsException
     */
    public function ensureIsNotRateLimited(): void
    {
        $maxAttempts = config('auth.rate_limiting.max_attempts', 5);

        if (! RateLimiter::tooManyAttempts($this->throttleKey(), $maxAttempts)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw new ThrottleRequestsException(
            'Too Many Attempts.',
            null,
            $this->getRateLimitHeaders($maxAttempts, 0, $seconds)
        );
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')->toString()) . '|' . $this->ip());
    }

    /**
     * Get the rate limit headers.
     *
     * @return array<string, mixed>
     */
    protected function getRateLimitHeaders(int $maxAttempts, int $remainingAttempts, int $retryAfter): array
    {
        return [
            'X-RateLimit-Limit'     => $maxAttempts,
            'X-RateLimit-Remaining' => $remainingAttempts,
            'Retry-After'           => $retryAfter,
            'X-RateLimit-Reset'     => time() + $retryAfter,
        ];
    }
}
