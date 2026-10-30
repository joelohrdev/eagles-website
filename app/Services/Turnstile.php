<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Cloudflare Turnstile bot check for public forms.
 *
 * Switched off until both TURNSTILE_SITE_KEY and TURNSTILE_SECRET_KEY are set,
 * so local development and the test suite work without Cloudflare keys.
 */
class Turnstile
{
    public const string VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    /**
     * The form field the Turnstile widget writes its token into.
     */
    public const string RESPONSE_FIELD = 'cf-turnstile-response';

    public function isEnabled(): bool
    {
        return filled(config('services.turnstile.site_key')) && filled(config('services.turnstile.secret_key'));
    }

    /**
     * The public site key for the widget, or null while Turnstile is off.
     */
    public function siteKey(): ?string
    {
        return $this->isEnabled() ? config('services.turnstile.site_key') : null;
    }

    /**
     * Ask Cloudflare whether a widget token is genuine. Fails closed when
     * Cloudflare cannot be reached.
     */
    public function verify(?string $token, ?string $ip = null): bool
    {
        if (blank($token)) {
            return false;
        }

        try {
            $response = Http::asForm()->timeout(5)->post(self::VERIFY_URL, array_filter([
                'secret' => config('services.turnstile.secret_key'),
                'response' => $token,
                'remoteip' => $ip,
            ]));
        } catch (ConnectionException) {
            return false;
        }

        return $response->successful() && $response->json('success') === true;
    }
}
