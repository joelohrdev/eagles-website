<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Mail } from '@lucide/vue';
import PageHero from '@/components/site/PageHero.vue';
import { Button } from '@/components/ui/button';
import { contact } from '@/routes';
import type { Coach } from '@/types/teams';

defineProps<{
    coaches: Coach[];
}>();

function initials(name: string): string {
    return name
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase() ?? '')
        .join('');
}
</script>

<template>
    <PageHero
        eyebrow="Eagles Baseball Travel"
        title="Coaching Staff"
        description="Experienced coaches dedicated to developing young players on and off the field."
    />

    <section class="container-site py-12 md:py-16">
        <div
            v-if="coaches.length === 0"
            class="rounded-lg border border-dashed p-12 text-center"
        >
            <h2
                class="font-display text-2xl font-bold tracking-wide text-navy uppercase dark:text-snow"
            >
                Staff bios coming soon
            </h2>
            <p class="mx-auto mt-2 max-w-md text-muted-foreground">
                We're putting together our coaching staff profiles. Have a
                question in the meantime? Get in touch.
            </p>
            <Button
                as-child
                class="mt-6 bg-sky font-semibold text-navy hover:bg-sky-dark hover:text-white"
            >
                <Link :href="contact()">Contact us</Link>
            </Button>
        </div>

        <div v-else class="grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
            <article
                v-for="coach in coaches"
                :key="coach.id"
                class="flex flex-col overflow-hidden rounded-lg border bg-card shadow-sm"
            >
                <div class="aspect-square w-full bg-muted">
                    <img
                        v-if="coach.photo_thumbnail_url"
                        :src="coach.photo_url ?? coach.photo_thumbnail_url"
                        :alt="`${coach.name} headshot`"
                        class="size-full object-cover"
                        loading="lazy"
                    />
                    <div
                        v-else
                        class="flex size-full items-center justify-center bg-navy text-snow"
                    >
                        <span class="font-display text-6xl font-bold">{{
                            initials(coach.name)
                        }}</span>
                    </div>
                </div>
                <div class="flex flex-1 flex-col gap-2 p-5">
                    <h2
                        class="font-display text-2xl font-bold tracking-wide text-navy uppercase dark:text-snow"
                    >
                        {{ coach.name }}
                    </h2>
                    <p
                        v-if="coach.title"
                        class="text-sm font-semibold tracking-wide text-sky uppercase"
                    >
                        {{ coach.title }}
                    </p>
                    <p
                        v-if="coach.bio"
                        class="text-sm leading-relaxed whitespace-pre-line text-muted-foreground"
                    >
                        {{ coach.bio }}
                    </p>
                    <a
                        v-if="coach.email"
                        :href="`mailto:${coach.email}`"
                        class="mt-auto inline-flex items-center gap-2 pt-2 text-sm font-medium text-sky-dark hover:underline"
                    >
                        <Mail class="size-4" /> {{ coach.email }}
                    </a>
                </div>
            </article>
        </div>
    </section>
</template>
