<script setup lang="ts">
import { money } from '@/lib/format';
import type { CampDetail } from '@/types/camps';

/**
 * Read-only schedule for a camp page: each session's days with the time for
 * every age group, prices, and which days are already full.
 */
const props = defineProps<{
    camp: Pick<CampDetail, 'sessions' | 'age_groups' | 'bundle_price'>;
}>();

function groupName(id: number | null): string | null {
    return props.camp.age_groups.find((group) => group.id === id)?.name ?? null;
}

function isFull(remaining: { remaining: number | null }[]): boolean {
    return (
        remaining.length > 0 && remaining.every((slot) => slot.remaining === 0)
    );
}

function fullGroups(
    remaining: { age_group_id: number | null; remaining: number | null }[],
): string[] {
    return remaining
        .filter((slot) => slot.remaining === 0 && slot.age_group_id !== null)
        .map((slot) => groupName(slot.age_group_id) ?? '');
}
</script>

<template>
    <div class="space-y-6">
        <div
            v-for="session in camp.sessions"
            :key="session.id"
            class="rounded-lg border bg-card"
        >
            <div
                class="flex flex-wrap items-baseline justify-between gap-2 border-b p-4"
            >
                <h3
                    class="font-display text-xl font-bold tracking-wide text-navy uppercase dark:text-snow"
                >
                    {{ session.name }}
                </h3>
                <p class="text-sm font-medium">
                    {{
                        session.price === null
                            ? 'Price TBD'
                            : session.price === 0
                              ? 'Free'
                              : `${money(session.price)}${session.days.length > 1 ? ' for all days' : ''}`
                    }}<span
                        v-if="
                            session.day_price !== null &&
                            session.days.length > 1
                        "
                        class="text-muted-foreground"
                    >
                        · {{ money(session.day_price) }} per day</span
                    >
                </p>
            </div>
            <p
                v-if="session.description"
                class="px-4 pt-3 text-sm text-muted-foreground"
            >
                {{ session.description }}
            </p>
            <ul class="divide-y">
                <li
                    v-for="day in session.days"
                    :key="day.id"
                    class="grid gap-1 p-4 sm:grid-cols-[11rem_1fr]"
                >
                    <p class="font-medium">
                        {{ day.title }}
                        <span
                            v-if="isFull(day.remaining)"
                            class="ml-1 text-xs font-semibold text-destructive uppercase"
                            >Full</span
                        >
                    </p>
                    <ul class="space-y-0.5 text-sm text-muted-foreground">
                        <li v-for="(time, t) in day.times" :key="t">
                            <span
                                v-if="groupName(time.age_group_id)"
                                class="font-medium text-foreground"
                                >{{ groupName(time.age_group_id) }}:
                            </span>
                            {{ time.range }}
                            <span
                                v-if="
                                    !isFull(day.remaining) &&
                                    fullGroups(day.remaining).includes(
                                        groupName(time.age_group_id) ?? '',
                                    )
                                "
                                class="text-xs font-semibold text-destructive uppercase"
                                >· Full</span
                            >
                        </li>
                    </ul>
                </li>
            </ul>
        </div>

        <p
            v-if="camp.bundle_price !== null"
            class="rounded-lg border border-sky/40 bg-accent p-4 text-sm text-navy"
        >
            Book every session for
            <strong>{{ money(camp.bundle_price) }}</strong
            >.
        </p>
    </div>
</template>
