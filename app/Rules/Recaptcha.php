<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;

/**
 * Verifies a Google reCAPTCHA v2 (checkbox) token server-side against
 * https://www.google.com/recaptcha/api/siteverify.
 *
 * A no-op (always passes) when CAPTCHA_SECRET_KEY isn't configured, mirroring
 * the "optional, hidden unless configured" pattern used for Google login —
 * so local dev/CI never needs real reCAPTCHA credentials.
 */
class Recaptcha implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $secret = config('services.recaptcha.secret_key');

        if (empty($secret)) {
            return;
        }

        if (empty($value)) {
            $fail('Please confirm the CAPTCHA before continuing.');

            return;
        }

        $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret' => $secret,
            'response' => $value,
        ]);

        if (! $response->ok() || ! $response->json('success')) {
            $fail('The CAPTCHA verification failed. Please try again.');
        }
    }
}
