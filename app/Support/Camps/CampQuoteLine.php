<?php

namespace App\Support\Camps;

use App\Models\CampSession;
use App\Models\CampSessionDay;
use Illuminate\Support\Collection;

/**
 * The price of the days picked from one session.
 */
final readonly class CampQuoteLine
{
    /**
     * @param  Collection<int, CampSessionDay>  $days
     */
    public function __construct(
        public CampSession $session,
        public Collection $days,
        public bool $wholeSession,
        public int $price,
    ) {}

    /**
     * "Pitching — Sat, Nov 21 & Mon, Nov 23".
     */
    public function description(): string
    {
        return "{$this->session->name} — ".$this->days->map(fn (CampSessionDay $day): string => $day->title())->join(', ', ' & ');
    }

    /**
     * @return array{session_id: int, name: string, description: string, day_ids: list<int>, whole_session: bool, price: int}
     */
    public function toArray(): array
    {
        return [
            'session_id' => $this->session->id,
            'name' => $this->session->name,
            'description' => $this->description(),
            'day_ids' => array_values($this->days->pluck('id')->all()),
            'whole_session' => $this->wholeSession,
            'price' => $this->price,
        ];
    }
}
