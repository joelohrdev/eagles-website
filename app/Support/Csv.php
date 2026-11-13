<?php

namespace App\Support;

/**
 * Writes CSV rows that are safe to open in Excel or Google Sheets.
 */
final class Csv
{
    /**
     * Write one row. Text starting with a formula character (= + - @, tab or
     * carriage return) gets a leading apostrophe, so a name or note typed into
     * a public form can't run as a spreadsheet formula when staff open the file.
     *
     * @param  resource  $handle
     * @param  list<string|int|float|null>  $cells
     */
    public static function writeRow($handle, array $cells): void
    {
        fputcsv($handle, array_map(self::neutralize(...), $cells), escape: '');
    }

    private static function neutralize(string|int|float|null $cell): string|int|float|null
    {
        if (is_string($cell) && $cell !== '' && in_array($cell[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'".$cell;
        }

        return $cell;
    }
}
