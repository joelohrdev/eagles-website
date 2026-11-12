<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import CoachGrid from '@/components/site/CoachGrid.vue';
import PageHero from '@/components/site/PageHero.vue';
import { Button } from '@/components/ui/button';
import { contact } from '@/routes';
import type { Coach } from '@/types/teams';

defineProps<{
    coaches: Coach[];
}>();
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

        <CoachGrid v-else :coaches="coaches" />
    </section>
</template>
