/** Format cents as US dollars, e.g. 2500 → "$25.00". */
export function money(cents: number | null | undefined): string {
    if (cents === null || cents === undefined) {
        return '';
    }

    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
    }).format(cents / 100);
}

/** "Sat, Aug 24, 2026". */
export function formatDate(value: string | null | undefined): string {
    if (!value) {
        return '';
    }

    return new Intl.DateTimeFormat('en-US', {
        weekday: 'short',
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        timeZone: 'America/Chicago',
    }).format(new Date(value));
}

/** "Sat, Aug 24, 2026 · 6:00 PM". */
export function formatDateTime(value: string | null | undefined): string {
    if (!value) {
        return '';
    }

    const date = new Date(value);
    const day = new Intl.DateTimeFormat('en-US', {
        weekday: 'short',
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        timeZone: 'America/Chicago',
    }).format(date);
    const time = new Intl.DateTimeFormat('en-US', {
        hour: 'numeric',
        minute: '2-digit',
        timeZone: 'America/Chicago',
    }).format(date);

    return `${day} · ${time}`;
}

/** "6:00 PM". */
export function formatTime(value: string | null | undefined): string {
    if (!value) {
        return '';
    }

    return new Intl.DateTimeFormat('en-US', {
        hour: 'numeric',
        minute: '2-digit',
        timeZone: 'America/Chicago',
    }).format(new Date(value));
}

/** Convert an ISO datetime to the value expected by <input type="datetime-local"> in Chicago time. */
export function toDateTimeLocal(value: string | null | undefined): string {
    if (!value) {
        return '';
    }

    const date = new Date(value);
    const parts = new Intl.DateTimeFormat('en-US', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        hour12: false,
        timeZone: 'America/Chicago',
    }).formatToParts(date);
    const get = (type: string) =>
        parts.find((p) => p.type === type)?.value ?? '00';
    const hour = get('hour') === '24' ? '00' : get('hour');

    return `${get('year')}-${get('month')}-${get('day')}T${hour}:${get('minute')}`;
}

/** Convert an ISO date (YYYY-MM-DD or datetime) to <input type="date"> value. */
export function toDateInput(value: string | null | undefined): string {
    if (!value) {
        return '';
    }

    return value.slice(0, 10);
}
