<script setup lang="ts">
import { CalendarDate, Time } from '@internationalized/date';
import { CalendarIcon, X } from '@lucide/vue';
import { TimeFieldInput, TimeFieldRoot } from 'reka-ui';
import type { DateValue, TimeValue } from 'reka-ui';
import { computed, ref, shallowRef, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import { Label } from '@/components/ui/label';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { cn } from '@/lib/utils';

/**
 * Date + time picker for admin forms. Renders a hidden input named `name`
 * whose value is `YYYY-MM-DDTHH:mm` (same shape as a native datetime-local
 * input, which is what the server already validates) or '' when cleared.
 * `modelValue` accepts the ISO string the server sends (interpreted in the
 * app timezone, America/Chicago).
 */
const props = withDefaults(
    defineProps<{
        name: string;
        label: string;
        modelValue?: string | null;
        id?: string;
        required?: boolean;
        optional?: boolean;
        withTime?: boolean;
        placeholder?: string;
        error?: string;
        hint?: string;
        /** Default time (HH:mm) applied when a date is picked before a time. */
        defaultTime?: string;
        /** Earliest / latest selectable date as YYYY-MM-DD. */
        min?: string;
        max?: string;
        /** Month the calendar opens on when empty, as YYYY-MM-DD (e.g. a likely birth year). */
        initialMonth?: string;
    }>(),
    {
        modelValue: null,
        id: undefined,
        required: false,
        optional: false,
        withTime: true,
        placeholder: 'Pick a date',
        error: undefined,
        hint: undefined,
        defaultTime: '09:00',
        min: undefined,
        max: undefined,
        initialMonth: undefined,
    },
);

function toCalendarDate(value: string | undefined): CalendarDate | undefined {
    if (!value) {
        return undefined;
    }

    const [y, m, d] = value.slice(0, 10).split('-').map(Number);

    return y && m && d ? new CalendarDate(y, m, d) : undefined;
}

const minValue = computed(() => toCalendarDate(props.min));
const maxValue = computed(() => toCalendarDate(props.max));
const defaultPlaceholder = computed(() => toCalendarDate(props.initialMonth));

const TIME_ZONE = 'America/Chicago';

const inputId = computed(() => props.id ?? props.name);

/** Break an ISO datetime into Chicago-local calendar date + time. */
function parseInitial(value: string | null): {
    date: CalendarDate | undefined;
    time: Time | undefined;
} {
    if (!value) {
        return { date: undefined, time: undefined };
    }

    const parsed = new Date(value);

    if (Number.isNaN(parsed.getTime())) {
        return { date: undefined, time: undefined };
    }

    const parts = new Intl.DateTimeFormat('en-US', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        hour12: false,
        timeZone: TIME_ZONE,
    }).formatToParts(parsed);
    const get = (type: string) =>
        Number(parts.find((p) => p.type === type)?.value ?? 0);
    const hour = get('hour') === 24 ? 0 : get('hour');

    return {
        date: new CalendarDate(get('year'), get('month'), get('day')),
        time: new Time(hour, get('minute')),
    };
}

const initial = parseInitial(props.modelValue);
const date = shallowRef<DateValue | undefined>(initial.date);
const time = shallowRef<TimeValue | undefined>(initial.time);
const open = ref(false);

watch(
    () => props.modelValue,
    (value) => {
        const next = parseInitial(value);
        date.value = next.date;
        time.value = next.time;
    },
);

watch(date, (value) => {
    if (value && props.withTime && !time.value) {
        const [h, m] = props.defaultTime.split(':').map(Number);
        time.value = new Time(h, m);
    }
});

const pad = (n: number) => String(n).padStart(2, '0');

const submitted = computed(() => {
    if (!date.value) {
        return '';
    }

    const d = `${date.value.year}-${pad(date.value.month)}-${pad(date.value.day)}`;

    if (!props.withTime) {
        return d;
    }

    const t = time.value ?? new Time(0, 0);

    return `${d}T${pad(t.hour)}:${pad(t.minute)}`;
});

const display = computed(() => {
    if (!date.value) {
        return '';
    }

    const jsDate = new Date(
        date.value.year,
        date.value.month - 1,
        date.value.day,
        time.value?.hour ?? 0,
        time.value?.minute ?? 0,
    );

    return new Intl.DateTimeFormat('en-US', {
        weekday: 'short',
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        ...(props.withTime ? { hour: 'numeric', minute: '2-digit' } : {}),
    }).format(jsDate);
});

function clear() {
    date.value = undefined;
    time.value = undefined;
}
</script>

<template>
    <div class="grid content-start gap-2">
        <Label :for="inputId">
            {{ label }}
            <span v-if="optional" class="text-muted-foreground"
                >(optional)</span
            >
        </Label>

        <input type="hidden" :name="name" :value="submitted" />

        <Popover v-model:open="open">
            <PopoverTrigger as-child>
                <Button
                    :id="inputId"
                    type="button"
                    variant="outline"
                    :class="
                        cn(
                            'w-full justify-start text-left font-normal',
                            !date && 'text-muted-foreground',
                        )
                    "
                    :aria-required="required || undefined"
                >
                    <CalendarIcon class="size-4" />
                    <span class="truncate">{{ display || placeholder }}</span>
                    <span
                        v-if="date && !required"
                        role="button"
                        tabindex="0"
                        class="ml-auto rounded-sm p-0.5 text-muted-foreground hover:bg-accent hover:text-foreground"
                        aria-label="Clear"
                        @click.stop.prevent="clear"
                        @keydown.enter.stop.prevent="clear"
                    >
                        <X class="size-3.5" />
                    </span>
                </Button>
            </PopoverTrigger>
            <PopoverContent class="w-auto p-0" align="start">
                <Calendar
                    v-model="date"
                    initial-focus
                    layout="month-and-year"
                    :week-starts-on="0"
                    :min-value="minValue"
                    :max-value="maxValue"
                    :default-placeholder="defaultPlaceholder"
                />
                <div
                    v-if="withTime"
                    class="flex items-center justify-between gap-3 border-t p-3"
                >
                    <span class="text-sm font-medium">Time</span>
                    <TimeFieldRoot
                        v-slot="{ segments }"
                        v-model="time"
                        :hour-cycle="12"
                        granularity="minute"
                        class="flex h-9 items-center rounded-md border border-input bg-background px-2 text-sm tabular-nums shadow-xs select-none focus-within:ring-2 focus-within:ring-ring/50"
                    >
                        <template v-for="item in segments" :key="item.part">
                            <TimeFieldInput
                                v-if="item.part === 'literal'"
                                :part="item.part"
                                class="px-0.5 text-muted-foreground"
                            >
                                {{ item.value }}
                            </TimeFieldInput>
                            <TimeFieldInput
                                v-else
                                :part="item.part"
                                class="rounded px-1 py-0.5 outline-none focus:bg-accent focus:text-accent-foreground data-[placeholder]:text-muted-foreground"
                            >
                                {{ item.value }}
                            </TimeFieldInput>
                        </template>
                    </TimeFieldRoot>
                </div>
                <div class="flex justify-end gap-2 border-t p-2">
                    <Button
                        v-if="date && !required"
                        type="button"
                        variant="ghost"
                        size="sm"
                        @click="clear"
                    >
                        Clear
                    </Button>
                    <Button type="button" size="sm" @click="open = false">
                        Done
                    </Button>
                </div>
            </PopoverContent>
        </Popover>

        <p v-if="hint" class="text-xs text-muted-foreground">{{ hint }}</p>
        <InputError :message="error" />
    </div>
</template>
