<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { Tabs } from '@/components/ui/tabs';
import type { ErrorTab } from '@/composables/useErrorTab';
import { useErrorTab } from '@/composables/useErrorTab';

/**
 * Tabs for an admin form whose fields span several tabs. Keeps every panel's
 * inputs in the submitted form and, when validation fails, opens the tab
 * holding the first error instead of leaving the page looking unchanged.
 */
const props = defineProps<{
    errors: Record<string, string>;
    tabs: ErrorTab[];
    class?: HTMLAttributes['class'];
}>();

const active = useErrorTab(
    () => props.errors,
    () => props.tabs,
);
</script>

<template>
    <Tabs v-model="active" :class="props.class">
        <slot />
    </Tabs>
</template>
