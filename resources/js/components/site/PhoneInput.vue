<script setup lang="ts">
import { nextTick, ref } from 'vue';
import { Input } from '@/components/ui/input';
import { formatPhone } from '@/lib/format';

/**
 * A US phone field that formats as the visitor types (555-123-4567). It stays
 * uncontrolled from the form's point of view: pass `name` and the enclosing
 * <Form> reads the formatted value like any other input.
 */
const phone = ref('');

/**
 * Take the raw keystroke first, then the formatted value, so the input still
 * re-renders when formatting strips a character and the value is unchanged.
 */
async function onInput(value: string | number): Promise<void> {
    phone.value = String(value);
    await nextTick();
    phone.value = formatPhone(phone.value);
}
</script>

<template>
    <Input
        type="tel"
        inputmode="numeric"
        placeholder="555-555-5555"
        :model-value="phone"
        @update:model-value="onInput"
    />
</template>
