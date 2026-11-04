<?php

namespace App\Services\Payments;

use Stripe\Exception\ApiConnectionException;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\AuthenticationException;
use Stripe\Exception\PermissionException;
use Stripe\StripeClient;

/**
 * Asks Stripe whether a key works for what this site does with it: starting checkouts.
 *
 * Listing one Checkout Session needs the same permission the restricted key is told to have
 * (Checkout Sessions: Write includes read) and creates nothing in the Stripe account.
 */
class StripeKeyChecker
{
    /**
     * @return string|null Null when Stripe accepts the key, otherwise a message for the admin.
     */
    public function problemWith(string $key): ?string
    {
        try {
            (new StripeClient($key))->checkout->sessions->all(['limit' => 1]);
        } catch (AuthenticationException) {
            return __('Stripe didn’t recognise that key. It may have been deleted or rolled, or copied with a character missing.');
        } catch (PermissionException) {
            return __('Stripe accepted the key, but it can’t start checkouts. Edit the key in Stripe and set Checkout Sessions to Write.');
        } catch (ApiConnectionException) {
            return __('This site couldn’t reach Stripe to check the key. Try again in a minute.');
        } catch (ApiErrorException $e) {
            return __('Stripe rejected the key: :message', ['message' => $e->getMessage()]);
        }

        return null;
    }
}
