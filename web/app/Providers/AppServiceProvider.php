<?php

declare(strict_types = 1);

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    #[\Override]
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
            throw new \RuntimeException('Frontend URL is not a string');
        }

        ResetPassword::createUrlUsing(function ($notifiable, string $token) use ($frontendUrl): string {
            if (! is_object($notifiable) || ! method_exists($notifiable, 'getEmailForPasswordReset')) {
                throw new \RuntimeException('Notifiable is not an object or does not have getEmailForPasswordReset method');
            }

            $email = $notifiable->getEmailForPasswordReset();

            if (! is_string($email)) {
                throw new \RuntimeException('getEmailForPasswordReset must return a string');
            }

            return $frontendUrl . "/password-reset/$token?email={$email}";
        });
    }
}
