<?php

namespace App\Rules;

use App\Services\Turnstile;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class PassesTurnstile implements ValidationRule
{
    public function __construct(private ?string $ip = null) {}

    /**
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! app(Turnstile::class)->verify(is_string($value) ? $value : null, $this->ip)) {
            $fail('Please confirm you are not a robot and try again.');
        }
    }
}
