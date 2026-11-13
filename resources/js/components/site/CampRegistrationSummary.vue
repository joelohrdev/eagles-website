<script setup lang="ts">
import { CalendarDays, MapPin } from '@lucide/vue';
import { formatDate, money } from '@/lib/format';
import type { CampDetail, CampQuote } from '@/types/camps';

/**
 * The camp registration summary: camp, dates, place, the priced selection and
 * the total. Shown beside the form on large screens and above the submit
 * button on phones, so the total is read before paying.
 */
defineProps<{
    camp: CampDetail;
    quote: CampQuote | null;
    /** Shown instead of the lines until something is selected. */
    prompt: string;
}>();
</script>

<template>
    <div class="rounded-lg border bg-card p-6 shadow-sm">
        <p
            class="text-xs font-semibold tracking-wide text-muted-foreground uppercase"
        >
            Summary
        </p>
        <p
            class="mt-2 font-display text-2xl font-bold tracking-wide text-navy uppercase dark:text-snow"
        >
            {{ camp.name }}
        </p>
        <ul class="mt-3 space-y-1.5 text-sm text-muted-foreground">
            <li class="flex items-center gap-2">
                <CalendarDays class="size-4 shrink-0 text-sky" />
                {{ formatDate(camp.starts_at) }}
                <template
                    v-if="
                        camp.ends_at &&
                        camp.ends_at.slice(0, 10) !==
                            camp.starts_at?.slice(0, 10)
                    "
                    >– {{ formatDate(camp.ends_at) }}</template
                >
            </li>
            <li
                v-if="camp.location || camp.address"
                class="flex items-start gap-2"
            >
                <MapPin class="mt-0.5 size-4 shrink-0 text-sky" />
                <span>
                    {{ camp.location }}
                    <span v-if="camp.address" class="block text-xs">{{
                        camp.address
                    }}</span>
                    <span v-if="camp.address_details" class="block text-xs">{{
                        camp.address_details
                    }}</span>
                </span>
            </li>
        </ul>
        <dl v-if="quote" class="mt-4 space-y-2 border-t pt-4 text-sm">
            <div
                v-for="line in quote.lines"
                :key="line.session_id"
                class="flex justify-between gap-3"
            >
                <dt class="text-muted-foreground">
                    {{ line.name }}
                    <span class="block text-xs">{{
                        line.whole_session
                            ? 'All days'
                            : `${line.day_ids.length} ${line.day_ids.length === 1 ? 'day' : 'days'}`
                    }}</span>
                </dt>
                <dd>{{ money(line.price) }}</dd>
            </div>
            <div
                v-if="quote.discount > 0"
                class="flex justify-between gap-3 text-sky-dark"
            >
                <dt>{{ quote.discount_label }}</dt>
                <dd>−{{ money(quote.discount) }}</dd>
            </div>
        </dl>
        <p v-else class="mt-4 border-t pt-4 text-sm text-muted-foreground">
            {{ prompt }}
        </p>
        <div class="mt-4 flex items-baseline justify-between border-t pt-4">
            <span class="text-sm text-muted-foreground">Total</span>
            <span
                class="font-display text-3xl font-bold text-navy dark:text-snow"
                >{{
                    !quote
                        ? '—'
                        : quote.total === 0
                          ? 'Free'
                          : money(quote.total)
                }}</span
            >
        </div>
        <p
            v-if="quote && quote.total > 0"
            class="mt-3 text-xs text-muted-foreground"
        >
            Your spot is held for 35 minutes while you complete payment.
        </p>
    </div>
</template>
