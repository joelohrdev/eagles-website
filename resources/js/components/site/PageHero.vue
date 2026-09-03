<script setup lang="ts">
withDefaults(
    defineProps<{
        title: string;
        eyebrow?: string;
        description?: string | null;
        imageUrl?: string | null;
        size?: 'default' | 'large';
    }>(),
    { eyebrow: undefined, description: null, imageUrl: null, size: 'default' },
);
</script>

<template>
    <section class="relative overflow-hidden bg-navy text-snow">
        <img
            v-if="imageUrl"
            :src="imageUrl"
            alt=""
            class="absolute inset-0 size-full object-cover opacity-30"
            loading="eager"
            fetchpriority="high"
        />
        <div
            class="absolute inset-0 bg-gradient-to-br from-navy via-navy/90 to-sky/40"
            aria-hidden="true"
        />
        <div
            class="relative container-site"
            :class="size === 'large' ? 'py-20 md:py-28' : 'py-12 md:py-16'"
        >
            <p
                v-if="eyebrow"
                class="mb-2 text-sm font-semibold tracking-widest text-sky uppercase"
            >
                {{ eyebrow }}
            </p>
            <h1
                class="font-display font-bold tracking-wide uppercase"
                :class="
                    size === 'large'
                        ? 'text-4xl sm:text-5xl md:text-6xl'
                        : 'text-3xl sm:text-4xl md:text-5xl'
                "
            >
                {{ title }}
            </h1>
            <p
                v-if="description"
                class="mt-4 max-w-2xl text-base text-stone md:text-lg"
            >
                {{ description }}
            </p>
            <div v-if="$slots.default" class="mt-6 flex flex-wrap gap-3">
                <slot />
            </div>
        </div>
    </section>
</template>
