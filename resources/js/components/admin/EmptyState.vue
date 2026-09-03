<script setup lang="ts">
withDefaults(
    defineProps<{
        title: string;
        description?: string;
    }>(),
    { description: undefined },
);
</script>

<template>
    <div
        class="flex flex-col items-center justify-center rounded-lg border border-dashed px-6 py-16 text-center"
    >
        <p class="font-medium">{{ title }}</p>
        <p
            v-if="description"
            class="mt-1 max-w-sm text-sm text-muted-foreground"
        >
            {{ description }}
        </p>
        <div v-if="$slots.default" class="mt-4">
            <slot />
        </div>
    </div>
</template>
