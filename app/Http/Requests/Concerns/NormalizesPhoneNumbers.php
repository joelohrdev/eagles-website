<?php

namespace App\Http\Requests\Concerns;

/**
 * US phone fields are stored as 999-999-9999 however they were typed or
 * pasted. Call normalizePhoneNumbers() from prepareForValidation(), then
 * validate each field with phoneRule().
 */
trait NormalizesPhoneNumbers
{
    protected function phoneRule(): string
    {
        return 'regex:/^\d{3}-\d{3}-\d{4}$/';
    }

    /**
     * @return array<string, string>
     */
    protected function phoneMessages(string ...$fields): array
    {
        return collect($fields)
            ->mapWithKeys(fn (string $field) => ["{$field}.regex" => 'Enter a 10-digit phone number, e.g. 555-555-5555.'])
            ->all();
    }

    /**
     * Rewrite each field with ten digits (or eleven with a leading US 1) as
     * 999-999-9999. Anything else is left alone for the regex rule to reject.
     */
    protected function normalizePhoneNumbers(string ...$fields): void
    {
        foreach ($fields as $field) {
            if (blank($this->input($field))) {
                continue;
            }

            $digits = preg_replace('/\D/', '', (string) $this->input($field));

            if (strlen($digits) === 11 && str_starts_with($digits, '1')) {
                $digits = substr($digits, 1);
            }

            if (strlen($digits) === 10) {
                $this->merge([
                    $field => substr($digits, 0, 3).'-'.substr($digits, 3, 3).'-'.substr($digits, 6),
                ]);
            }
        }
    }
}
