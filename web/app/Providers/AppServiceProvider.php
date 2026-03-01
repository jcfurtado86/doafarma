<?php

declare(strict_types = 1);

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Override;
use RuntimeException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    #[Override]
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $frontendUrl = config('app.frontend_url');

        if (! is_string($frontendUrl)) {
            throw new RuntimeException('Frontend URL is not a string');
        }

        ResetPassword::createUrlUsing(function ($notifiable, string $token) use ($frontendUrl): string {
            if (! is_object($notifiable) || ! method_exists($notifiable, 'getEmailForPasswordReset')) {
                throw new RuntimeException('Notifiable is not an object or does not have getEmailForPasswordReset method');
            }

            $email = $notifiable->getEmailForPasswordReset();

            if (! is_string($email)) {
                throw new RuntimeException('getEmailForPasswordReset must return a string');
            }

            return $frontendUrl . "/password-reset/$token?email={$email}";
        });

        $this->configureRateLimiting();
    }

    private function configureRateLimiting(): void
    {
        $maxAttempts  = config('auth.rate_limiting.max_attempts');
        $decayMinutes = config('auth.rate_limiting.decay_minutes');

        $maxAttempts  = is_int($maxAttempts) ? $maxAttempts : 5;
        $decayMinutes = is_int($decayMinutes) ? $decayMinutes : 1;

        RateLimiter::for('login', function (Request $request) use ($maxAttempts, $decayMinutes): Limit {
            $email = $request->string('email')->toString();
            $ip    = $request->ip() ?? 'unknown';

            $key = $email !== ''
                ? Str::transliterate(Str::lower($email)) . '|' . $ip
                : $ip;

            return Limit::perMinutes($decayMinutes, $maxAttempts)->by($key);
        });

        RateLimiter::for('auth', function (Request $request): Limit {
            $userId = $request->user()?->id;
            $ip     = $request->ip() ?? 'unknown';

            $key = $userId !== null
                ? $userId . '|' . $ip
                : $ip;

            return Limit::perMinute(10)->by($key);
        });
    }
}
