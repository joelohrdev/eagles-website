<script setup lang="ts">
import { ref, watch } from 'vue';
import { Input } from '@/components/ui/input';

/**
 * Dollar amount field. A text input (decimal keypad on phones, no spinner
 * arrows or scroll-wheel changes) with a $ prefix, tidied to two decimals when
 * it loses focus: "62.5" becomes "62.50", "$1,250" becomes "1250.00".
 * Use v-model, or `default-value` for an uncontrolled field. Other attributes
 * (name, id, placeholder, required, aria-label, class) go to the input.
 */
defineOptions({ inheritAttrs: false });

const props = defineProps<{
    defaultValue?: string | number | null;
}>();

const model = defineModel<string>();
const text = ref(format(model.value ?? props.defaultValue ?? ''));

watch(model, (value) => {
    if (value !== undefined && value !== text.value) {
        text.value = format(value);
    }
});

function format(value: string | number | null): string {
    const cleaned = String(value ?? '')
        .replace(/[$,\s]/g, '')
        .trim();

    if (cleaned === '' || !/^\d*\.?\d*$/.test(cleaned) || cleaned === '.') {
        return cleaned;
    }

    return Number(cleaned).toFixed(2);
}

function update(value: string | number) {
    text.value = String(value);
    model.value = text.value;
}

function tidy() {
    update(format(text.value));
}
</script>

<template>
    <div class="relative">
        <span
            class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-sm text-muted-foreground"
            aria-hidden="true"
            >$</span
        >
        <Input
            v-bind="$attrs"
            :model-value="text"
            type="text"
            inputmode="decimal"
            autocomplete="off"
            class="pl-7"
            @update:model-value="update"
            @blur="tidy"
        />
    </div>
</template>
