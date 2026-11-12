<script setup lang="ts">
import { Time } from '@internationalized/date';
import { Clock } from '@lucide/vue';
import type { TimeValue } from 'reka-ui';
import { TimeFieldInput, TimeFieldRoot } from 'reka-ui';
import { computed } from 'vue';

/**
 * Time-of-day field (reka-ui TimeField, styled like the shadcn Input) bound to
 * an `HH:mm` string in 24-hour form, shown to people as 12-hour time. Renders a
 * hidden input named `name` so it submits inside an Inertia <Form>.
 */
withDefaults(
    defineProps<{
        name?: string;
        id?: string;
        label?: string;
        invalid?: boolean;
    }>(),
    {
        name: undefined,
        id: undefined,
        label: undefined,
        invalid: false,
    },
);

const model = defineModel<string>({ default: '' });

const pad = (n: number) => String(n).padStart(2, '0');

const time = computed<TimeValue | undefined>({
    get() {
        const [h, m] = model.value.split(':').map(Number);

        return model.value !== '' && !Number.isNaN(h) && !Number.isNaN(m)
            ? new Time(h, m)
            : undefined;
    },
    set(value) {
        model.value = value ? `${pad(value.hour)}:${pad(value.minute)}` : '';
    },
});
</script>

<template>
    <div>
        <input v-if="name" type="hidden" :name="name" :value="model" />
        <TimeFieldRoot
            :id="id"
            v-slot="{ segments }"
            v-model="time"
            :hour-cycle="12"
            granularity="minute"
            :aria-label="label"
            :aria-invalid="invalid || undefined"
            class="flex h-9 w-full items-center gap-1.5 rounded-md border border-input bg-transparent px-3 text-sm tabular-nums shadow-xs select-none focus-within:border-ring focus-within:ring-3 focus-within:ring-ring/50 aria-invalid:border-destructive dark:bg-input/30"
        >
            <Clock class="size-4 shrink-0 text-muted-foreground" />
            <template v-for="item in segments" :key="item.part">
                <TimeFieldInput
                    v-if="item.part === 'literal'"
                    :part="item.part"
                    class="text-muted-foreground"
                >
                    {{ item.value }}
                </TimeFieldInput>
                <TimeFieldInput
                    v-else
                    :part="item.part"
                    class="rounded px-0.5 py-0.5 outline-none focus:bg-accent focus:text-accent-foreground data-[placeholder]:text-muted-foreground"
                >
                    {{ item.value }}
                </TimeFieldInput>
            </template>
        </TimeFieldRoot>
    </div>
</template>
