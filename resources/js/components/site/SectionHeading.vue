<script setup lang="ts">
withDefaults(
    defineProps<{
        title: string;
        eyebrow?: string;
        description?: string | null;
        align?: 'left' | 'center';
    }>(),
    { eyebrow: undefined, description: null, align: 'left' },
);
</script>

<template>
    <div
        :class="
            align === 'center' ? 'mx-auto max-w-2xl text-center' : 'max-w-2xl'
        "
    >
        <p
            v-if="eyebrow"
            class="mb-1 text-sm font-semibold tracking-widest text-sky uppercase"
        >
            {{ eyebrow }}
        </p>
        <h2
            class="font-display text-3xl font-bold tracking-wide text-navy uppercase md:text-4xl dark:text-snow"
        >
            {{ title }}
        </h2>
        <p v-if="description" class="mt-3 text-muted-foreground">
            {{ description }}
        </p>
    </div>
</template>
