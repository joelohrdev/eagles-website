<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { CalendarDays, MapPin, Users } from '@lucide/vue';
import PageHero from '@/components/site/PageHero.vue';
import RegistrationStateBadge from '@/components/site/RegistrationStateBadge.vue';
import { Button } from '@/components/ui/button';
import { formatDate, formatTime, money } from '@/lib/format';
import { contact } from '@/routes';
import type { CampSummary } from '@/types/camps';

defineProps<{
    camps: CampSummary[];
}>();

function dateRange(camp: CampSummary): string {
    if (!camp.ends_at) {
        return `${formatDate(camp.starts_at)} · ${formatTime(camp.starts_at)}`;
    }

    const sameDay = camp.starts_at.slice(0, 10) === camp.ends_at.slice(0, 10);

    return sameDay
        ? `${formatDate(camp.starts_at)} · ${formatTime(camp.starts_at)} – ${formatTime(camp.ends_at)}`
        : `${formatDate(camp.starts_at)} – ${formatDate(camp.ends_at)}`;
}
</script>

<template>
    <PageHero
        eyebrow="Train with the Eagles"
        title="Camps & Clinics"
        description="Skills camps and position clinics run by our coaching staff — hitting, pitching, fielding, catching, and more."
    />

    <section class="container-site py-12 md:py-16">
        <p class="max-w-3xl text-lg text-muted-foreground">
            Eagles Baseball Travel offers seasonal baseball camps and clinics
            for youth players ages 9U–17U. Camps are open to all players — you
            don't need to be on an Eagles team to attend. Register online below;
            spots are first come, first served.
        </p>

        <div
            v-if="camps.length === 0"
            class="mt-10 rounded-lg border border-dashed p-10 text-center"
        >
            <p
                class="font-display text-2xl font-bold text-navy uppercase dark:text-snow"
            >
                No camps scheduled right now
            </p>
            <p class="mx-auto mt-2 max-w-md text-muted-foreground">
                We run camps throughout the year. Check back soon, or reach out
                and we'll let you know when the next one opens.
            </p>
            <Button
                as-child
                class="mt-6 bg-sky font-semibold text-navy hover:bg-sky-dark hover:text-white"
            >
                <Link :href="contact()">Contact us</Link>
            </Button>
        </div>

        <div v-else class="mt-10 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            <article
                v-for="camp in camps"
                :key="camp.id"
                class="flex flex-col overflow-hidden rounded-lg border bg-card shadow-sm transition-shadow hover:shadow-md"
            >
                <Link :href="camp.url" class="block">
                    <img
                        v-if="camp.image_thumbnail_url"
                        :src="camp.image_thumbnail_url"
                        :alt="camp.name"
                        loading="lazy"
                        class="aspect-video w-full object-cover"
                    />
                    <div
                        v-else
                        class="flex aspect-video w-full items-center justify-center bg-navy text-snow"
                    >
                        <CalendarDays class="size-10 text-sky" />
                    </div>
                </Link>
                <div class="flex flex-1 flex-col p-5">
                    <div class="flex items-start justify-between gap-2">
                        <h2
                            class="font-display text-2xl font-bold tracking-wide text-navy uppercase dark:text-snow"
                        >
                            <Link :href="camp.url" class="hover:text-sky">{{
                                camp.name
                            }}</Link>
                        </h2>
                        <span
                            class="shrink-0 rounded-md bg-accent px-2 py-1 text-sm font-semibold text-navy"
                        >
                            {{ camp.is_free ? 'Free' : money(camp.price) }}
                        </span>
                    </div>
                    <ul class="mt-3 space-y-1.5 text-sm text-muted-foreground">
                        <li class="flex items-center gap-2">
                            <CalendarDays class="size-4 shrink-0 text-sky" />
                            {{ dateRange(camp) }}
                        </li>
                        <li
                            v-if="camp.location"
                            class="flex items-center gap-2"
                        >
                            <MapPin class="size-4 shrink-0 text-sky" />
                            {{ camp.location }}
                        </li>
                        <li
                            v-if="camp.age_range"
                            class="flex items-center gap-2"
                        >
                            <Users class="size-4 shrink-0 text-sky" /> Ages
                            {{ camp.age_range }}
                        </li>
                    </ul>
                    <div class="mt-4">
                        <RegistrationStateBadge
                            :state="camp.registration_state"
                            :spots-remaining="camp.spots_remaining"
                        />
                    </div>
                    <div class="mt-5 flex gap-2 pt-1">
                        <Button as-child variant="outline" class="flex-1">
                            <Link :href="camp.url">Details</Link>
                        </Button>
                        <Button
                            v-if="camp.registration_state === 'open'"
                            as-child
                            class="flex-1 bg-sky font-semibold text-navy hover:bg-sky-dark hover:text-white"
                        >
                            <Link :href="camp.register_url">Register</Link>
                        </Button>
                    </div>
                </div>
            </article>
        </div>
    </section>
</template>
