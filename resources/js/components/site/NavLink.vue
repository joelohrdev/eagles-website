<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { NavigationLink } from '@/types';

/**
 * Renders a navigation item as an Inertia <Link> for internal pages or a plain
 * <a> for external / mailto / tel targets, honoring the "open in new tab" flag.
 */
defineProps<{
    item: NavigationLink;
}>();
</script>

<template>
    <a
        v-if="item.external || item.new_tab"
        :href="item.href"
        :target="item.new_tab ? '_blank' : undefined"
        :rel="item.new_tab ? 'noopener noreferrer' : undefined"
    >
        <slot>{{ item.label }}</slot>
    </a>
    <Link v-else :href="item.href" prefetch>
        <slot>{{ item.label }}</slot>
    </Link>
</template>
