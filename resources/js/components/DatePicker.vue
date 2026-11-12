<script setup lang="ts">
import { CalendarDate } from '@internationalized/date';
import { CalendarIcon } from '@lucide/vue';
import type { DateValue } from 'reka-ui';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { cn } from '@/lib/utils';

/**
 * Date-only picker (shadcn Popover + Calendar) bound to a `YYYY-MM-DD` string.
 * Renders a hidden input named `name` so it submits inside an Inertia <Form>.
 * Dates are calendar days, so no timezone conversion happens.
 */
const props = withDefaults(
    defineProps<{
        name?: string;
        id?: string;
        placeholder?: string;
        required?: boolean;
        invalid?: boolean;
    }>(),
    {
        name: undefined,
        id: undefined,
        placeholder: 'Pick a date',
        required: false,
        invalid: false,
    },
);

const model = defineModel<string>({ default: '' });
const open = ref(false);

const pad = (n: number) => String(n).padStart(2, '0');

const date = computed<DateValue | undefined>({
    get() {
        const [y, m, d] = model.value.split('-').map(Number);

        return y && m && d ? new CalendarDate(y, m, d) : undefined;
    },
    set(value) {
        model.value = value
            ? `${value.year}-${pad(value.month)}-${pad(value.day)}`
            : '';

        if (value) {
            open.value = false;
        }
    },
});

const display = computed(() =>
    date.value
        ? new Intl.DateTimeFormat('en-US', {
              weekday: 'short',
              month: 'short',
              day: 'numeric',
              year: 'numeric',
          }).format(
              new Date(date.value.year, date.value.month - 1, date.value.day),
          )
        : '',
);
</script>

<template>
    <div>
        <input v-if="name" type="hidden" :name="name" :value="model" />
        <Popover v-model:open="open">
            <PopoverTrigger as-child>
                <Button
                    :id="props.id"
                    type="button"
                    variant="outline"
                    :aria-required="required || undefined"
                    :aria-invalid="invalid || undefined"
                    :class="
                        cn(
                            'w-full justify-start text-left font-normal',
                            !date && 'text-muted-foreground',
                        )
                    "
                >
                    <CalendarIcon class="size-4" />
                    <span class="truncate">{{ display || placeholder }}</span>
                </Button>
            </PopoverTrigger>
            <PopoverContent class="w-auto p-0" align="start">
                <Calendar
                    v-model="date"
                    initial-focus
                    layout="month-and-year"
                    :week-starts-on="0"
                />
            </PopoverContent>
        </Popover>
    </div>
</template>
