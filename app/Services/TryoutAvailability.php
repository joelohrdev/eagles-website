<?php

namespace App\Services;

use App\Models\Tryout;
use Illuminate\Support\Str;

/**
 * Single source of truth for whether the site advertises tryouts.
 *
 * Every tryout link and button on the public site is hidden automatically
 * while no tryout is open for registration, so nothing has to be toggled
 * by hand in Site Settings or the navigation menus. The `/tryouts` page
 * itself stays reachable and shows its own empty state.
 *
 * Bound as `scoped` so the lookup runs once per request (and is dropped
 * between requests and queued jobs) — the answer moves with the clock,
 * so it must never be cached beyond the current request.
 */
class TryoutAvailability
{
    private ?bool $isOpen = null;

    /**
     * Is at least one tryout accepting registrations right now?
     */
    public function isOpen(): bool
    {
        return $this->isOpen ??= Tryout::query()->openForRegistration()->exists();
    }

    /**
     * Does this href point at a public tryouts page?
     */
    public function linksToTryouts(?string $href): bool
    {
        if (blank($href)) {
            return false;
        }

        $path = '/'.trim((string) parse_url($href, PHP_URL_PATH), '/');
        $tryouts = route('tryouts.index', absolute: false);

        return $path === $tryouts || Str::startsWith($path, $tryouts.'/');
    }

    /**
     * Should a link to this href be hidden from the site?
     */
    public function hidesLink(?string $href): bool
    {
        return $this->linksToTryouts($href) && ! $this->isOpen();
    }
}
