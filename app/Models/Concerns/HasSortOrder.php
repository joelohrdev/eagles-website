<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\DB;

/**
 * Manual drag-and-drop ordering backed by a `sort_order` column.
 *
 * The consuming model must define an `ordered()` scope that sorts by
 * `sort_order` before any tiebreaker.
 */
trait HasSortOrder
{
    /**
     * The slot at the end of the list, for a newly created record.
     */
    public static function nextSortOrder(): int
    {
        return ((int) static::query()->max('sort_order')) + 1;
    }

    /**
     * Persist the order the given ids were dragged into.
     *
     * The ids are one page of the list, so only the slots those records
     * already occupy are reshuffled between them — records on other pages
     * keep their place. Every row is normalised to a unique slot first, so
     * the stored order alone decides the sequence from then on.
     *
     * @param  list<int>  $ids
     */
    public static function applyManualOrder(array $ids): void
    {
        DB::transaction(function () use ($ids): void {
            $records = static::query()->ordered()->get();

            /** @var array<int, int> $positions record id => position in the displayed order */
            $positions = [];

            foreach ($records as $position => $record) {
                $positions[(int) $record->getKey()] = $position;
            }

            $moved = array_values(array_filter($ids, fn (int $id): bool => isset($positions[$id])));
            $slots = array_map(fn (int $id): int => $positions[$id], $moved);
            sort($slots);

            foreach ($moved as $index => $id) {
                $positions[$id] = $slots[$index];
            }

            foreach ($records as $record) {
                $position = $positions[(int) $record->getKey()];

                if ((int) $record->getAttribute('sort_order') !== $position) {
                    static::query()->whereKey($record->getKey())->update(['sort_order' => $position]);
                }
            }
        });
    }
}
