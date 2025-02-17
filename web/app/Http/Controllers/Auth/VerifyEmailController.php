<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     */
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        $frontendUrl = config('app.frontend_url');

        if (! is_string($frontendUrl)) {
            throw new \RuntimeException('Frontend URL is not a string');
        }

        if (! $request->user()) {
            return redirect()->intended(
                $frontendUrl . '/login'
            );
        }

        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(
                $frontendUrl . '/dashboard?verified=1'
            );
        }

        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));
        }

        return redirect()->intended(
            $frontendUrl . '/dashboard?verified=1'
        );
    }
}
