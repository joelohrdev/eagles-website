<?php

namespace App\Services;

use App\Models\SiteSetting;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

/**
 * Stripe credentials entered in the admin, stored encrypted in site_settings.
 *
 * Saved values win over STRIPE_SECRET / STRIPE_WEBHOOK_SECRET in .env, so a server
 * that was set up through .env keeps working until someone saves keys here.
 */
class StripeSettings
{
    public const string SECRET_KEY = 'stripe_secret_key';

    public const string WEBHOOK_SECRET = 'stripe_webhook_secret';

    public const string KEY_CHECKED_AT = 'stripe_key_checked_at';

    public const string WEBHOOK_RECEIVED_AT = 'stripe_webhook_received_at';

    public const string WEBHOOK_REJECTED_AT = 'stripe_webhook_rejected_at';

    /**
     * Keys kept out of SiteSettings::all(): ciphertext must never land in page props, and the
     * timestamps change on every webhook, which the forever-cached settings can't follow.
     *
     * @var list<string>
     */
    public const array KEYS = [self::SECRET_KEY, self::WEBHOOK_SECRET, self::KEY_CHECKED_AT, self::WEBHOOK_RECEIVED_AT, self::WEBHOOK_REJECTED_AT];

    public function secretKey(): ?string
    {
        return $this->stored(self::SECRET_KEY) ?? (filled(config('services.stripe.secret')) ? (string) config('services.stripe.secret') : null);
    }

    public function webhookSecret(): ?string
    {
        return $this->stored(self::WEBHOOK_SECRET) ?? (filled(config('services.stripe.webhook_secret')) ? (string) config('services.stripe.webhook_secret') : null);
    }

    public function isTestMode(): bool
    {
        return Str::contains((string) $this->secretKey(), '_test_');
    }

    /**
     * Where each credential currently comes from, with only its last four characters showing.
     *
     * along with when Stripe last accepted the key and when a webhook last arrived.
     *
     * @return array{secretKey: array{masked: string|null, source: 'dashboard'|'env'|null}, webhookSecret: array{masked: string|null, source: 'dashboard'|'env'|null}, testMode: bool, keyCheckedAt: string|null, webhookReceivedAt: string|null, webhookRejectedAt: string|null}
     */
    public function summary(): array
    {
        return [
            'secretKey' => $this->describe(self::SECRET_KEY, $this->secretKey()),
            'webhookSecret' => $this->describe(self::WEBHOOK_SECRET, $this->webhookSecret()),
            'testMode' => $this->isTestMode(),
            'keyCheckedAt' => $this->timestamp(self::KEY_CHECKED_AT),
            'webhookReceivedAt' => $this->timestamp(self::WEBHOOK_RECEIVED_AT),
            'webhookRejectedAt' => $this->timestamp(self::WEBHOOK_REJECTED_AT),
        ];
    }

    /**
     * Note that Stripe just accepted the current key.
     */
    public function markKeyChecked(): void
    {
        $this->touch(self::KEY_CHECKED_AT);
    }

    /**
     * Note a webhook delivery: verified means its signature matched the saved signing secret.
     */
    public function recordWebhook(bool $verified): void
    {
        $this->touch($verified ? self::WEBHOOK_RECEIVED_AT : self::WEBHOOK_REJECTED_AT);
    }

    /**
     * Save whichever values were given; a blank value keeps what is already stored.
     *
     * A new key has already been checked with Stripe by the request. A new signing secret
     * clears the webhook history, which described deliveries checked against the old one.
     */
    public function save(?string $secretKey, ?string $webhookSecret): void
    {
        foreach ([self::SECRET_KEY => $secretKey, self::WEBHOOK_SECRET => $webhookSecret] as $key => $value) {
            if (blank($value)) {
                continue;
            }

            SiteSetting::query()->updateOrCreate(['key' => $key], ['value' => Crypt::encryptString(trim($value))]);
        }

        if (filled($secretKey)) {
            $this->markKeyChecked();
        }

        if (filled($webhookSecret)) {
            SiteSetting::query()->whereIn('key', [self::WEBHOOK_RECEIVED_AT, self::WEBHOOK_REJECTED_AT])->delete();
        }
    }

    private function touch(string $key): void
    {
        SiteSetting::query()->updateOrCreate(['key' => $key], ['value' => now()->toIso8601String()]);
    }

    private function timestamp(string $key): ?string
    {
        $value = SiteSetting::query()->where('key', $key)->value('value');

        return blank($value) ? null : Carbon::parse($value)->toIso8601String();
    }

    private function stored(string $key): ?string
    {
        $encrypted = SiteSetting::query()->where('key', $key)->value('value');

        if (blank($encrypted)) {
            return null;
        }

        /** A rotated APP_KEY makes old ciphertext unreadable; fall back to .env rather than crash checkout. */
        try {
            return Crypt::decryptString($encrypted);
        } catch (DecryptException) {
            return null;
        }
    }

    /**
     * @return array{masked: string|null, source: 'dashboard'|'env'|null}
     */
    private function describe(string $key, ?string $value): array
    {
        if ($value === null) {
            return ['masked' => null, 'source' => null];
        }

        return [
            'masked' => Str::match('/^((?:sk|rk)_(?:test|live)_|whsec_)/', $value).'…'.Str::substr($value, -4),
            'source' => $this->stored($key) !== null ? 'dashboard' : 'env',
        ];
    }
}
