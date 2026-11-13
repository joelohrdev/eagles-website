<?php

namespace App\Services;

use App\Models\Camp;
use Illuminate\Contracts\Session\Session;
use Illuminate\Http\Request;

/**
 * Who may open a camp's preview pages (which also work for drafts): any
 * signed-in staff member, or a browser that entered the camp's 4-digit
 * passcode. A passcode grant is tied to the code itself, so changing or
 * clearing the passcode locks out everyone who used the old one.
 */
class CampPreviewAccess
{
    public const string SESSION_KEY = 'camp_previews';

    public function allows(Request $request, Camp $camp): bool
    {
        if ($request->user() !== null) {
            return true;
        }

        return $this->hasPasscode($camp)
            && hash_equals($this->fingerprint($camp), (string) $request->session()->get(self::SESSION_KEY.'.'.$camp->id, ''));
    }

    /**
     * An archived camp is kept for staff only, so its share passcode stops working.
     */
    public function hasPasscode(Camp $camp): bool
    {
        return ! $camp->isArchived() && filled($camp->preview_passcode);
    }

    /**
     * Check a typed passcode and, when it matches, let this browser in.
     */
    public function unlock(Session $session, Camp $camp, string $passcode): bool
    {
        if (! $this->hasPasscode($camp) || ! hash_equals((string) $camp->preview_passcode, $passcode)) {
            return false;
        }

        $session->put(self::SESSION_KEY.'.'.$camp->id, $this->fingerprint($camp));

        return true;
    }

    /**
     * A keyed hash of the current passcode, so the session never holds the code itself.
     */
    private function fingerprint(Camp $camp): string
    {
        return hash_hmac('sha256', $camp->id.'|'.$camp->preview_passcode, (string) config('app.key'));
    }
}
