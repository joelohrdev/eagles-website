<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronRight } from '@lucide/vue';
import { formatDateTime, money } from '@/lib/format';
import type { ActivityEntry } from '@/types/activity';

/**
 * A timeline of activity log entries with expandable details. Used on the
 * Activity page and in an order's history.
 */
defineProps<{
    entries: ActivityEntry[];
    /** Hide the "View order" style links (e.g. when already on that order). */
    hideLinks?: boolean;
}>();

/** Amounts are stored in cents. */
const MONEY_KEYS = ['total', 'discount'];

function label(key: string): string {
    const words = key.replace(/_/g, ' ');

    return words.charAt(0).toUpperCase() + words.slice(1);
}

function value(key: string, raw: unknown): string {
    if (MONEY_KEYS.includes(key) && typeof raw === 'number') {
        return money(raw);
    }

    if (Array.isArray(raw)) {
        return raw.length ? raw.join('; ') : '—';
    }

    if (typeof raw === 'boolean') {
        return raw ? 'Yes' : 'No';
    }

    return raw === null || raw === undefined || raw === '' ? '—' : String(raw);
}

function actorClass(entry: ActivityEntry): string {
    if (entry.is_staff) {
        return 'bg-navy text-snow';
    }

    return (
        {
            Stripe: 'bg-[#635bff] text-white',
            System: 'bg-muted text-muted-foreground',
        }[entry.actor] ?? 'bg-accent text-sky-dark'
    );
}
</script>

<template>
    <ol class="divide-y rounded-lg border">
        <li v-for="entry in entries" :key="entry.id" class="p-3 sm:p-4">
            <details class="group">
                <summary
                    class="flex cursor-pointer list-none flex-wrap items-start gap-x-3 gap-y-1 [&::-webkit-details-marker]:hidden"
                >
                    <ChevronRight
                        class="mt-0.5 size-4 shrink-0 text-muted-foreground transition-transform group-open:rotate-90"
                    />
                    <span class="min-w-0 flex-1">
                        <span class="block text-sm font-medium">{{
                            entry.description
                        }}</span>
                        <span class="text-xs text-muted-foreground">
                            {{ formatDateTime(entry.created_at) }}
                        </span>
                    </span>
                    <span
                        class="rounded-full px-2 py-0.5 text-xs font-medium whitespace-nowrap"
                        :class="actorClass(entry)"
                        >{{ entry.actor }}</span
                    >
                </summary>
                <div class="mt-3 ml-7 space-y-2 text-sm">
                    <dl
                        v-if="Object.keys(entry.properties).length"
                        class="grid gap-x-4 gap-y-1 sm:grid-cols-[10rem_1fr]"
                    >
                        <template
                            v-for="(raw, key) in entry.properties"
                            :key="key"
                        >
                            <dt class="text-muted-foreground">
                                {{ label(String(key)) }}
                            </dt>
                            <dd class="break-words">
                                {{ value(String(key), raw) }}
                            </dd>
                        </template>
                    </dl>
                    <p class="text-xs text-muted-foreground">
                        Event <code>{{ entry.event }}</code>
                        <template v-if="entry.ip_address">
                            · IP {{ entry.ip_address }}</template
                        >
                    </p>
                    <Link
                        v-if="entry.link && !hideLinks"
                        :href="entry.link.url"
                        class="inline-block text-sm font-medium text-sky-dark hover:underline"
                        >{{ entry.link.label }}</Link
                    >
                </div>
            </details>
        </li>
    </ol>
</template>
