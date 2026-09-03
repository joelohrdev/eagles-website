<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { CalendarDays, MapPin } from '@lucide/vue';
import PageHero from '@/components/site/PageHero.vue';
import RegistrationStateBadge from '@/components/site/RegistrationStateBadge.vue';
import { Button } from '@/components/ui/button';
import { formatDateTime } from '@/lib/format';
import { contact } from '@/routes';
import type { Tryout } from '@/types/tryouts';

defineProps<{
    tryouts: Tryout[];
}>();
</script>

<template>
    <PageHero
        eyebrow="Join the Eagles"
        title="Tryouts"
        description="Upcoming tryout dates for our travel teams. Registration is free — pick your division, sign up, and show us what you've got."
    />

    <section class="container-site py-12 md:py-16">
        <p class="max-w-3xl text-lg text-muted-foreground">
            Eagles Baseball Travel holds tryouts for 9U–17U travel teams. Each
            tryout is division-specific and held at our training facility unless
            noted. Register online below; you'll receive a confirmation email
            with everything you need to know.
        </p>

        <div
            v-if="tryouts.length === 0"
            class="mt-10 rounded-lg border border-dashed p-10 text-center"
        >
            <p
                class="font-display text-2xl font-bold tracking-wide text-navy uppercase dark:text-snow"
            >
                No tryouts scheduled
            </p>
            <p class="mt-2 text-muted-foreground">
                Check back soon or contact us to be notified when dates are
                announced.
            </p>
            <Button
                as-child
                class="mt-6 bg-sky font-semibold text-navy hover:bg-sky-dark hover:text-white"
            >
                <Link :href="contact()">Contact us</Link>
            </Button>
        </div>

        <ul v-else class="mt-10 grid gap-6 md:grid-cols-2 xl:grid-cols-3">
            <li
                v-for="tryout in tryouts"
                :key="tryout.id"
                class="flex flex-col overflow-hidden rounded-lg border bg-card shadow-sm"
            >
                <Link :href="tryout.url!" class="block">
                    <img
                        v-if="tryout.image_thumbnail_url"
                        :src="tryout.image_thumbnail_url"
                        :alt="tryout.title"
                        loading="lazy"
                        class="aspect-video w-full object-cover"
                    />
                    <div
                        v-else
                        class="flex aspect-video w-full items-center justify-center bg-navy text-snow"
                    >
                        <span
                            class="font-display text-4xl font-bold uppercase"
                            >{{ tryout.division }}</span
                        >
                    </div>
                </Link>
                <div class="flex flex-1 flex-col gap-3 p-5">
                    <div class="flex items-start justify-between gap-2">
                        <h2
                            class="font-display text-2xl font-bold tracking-wide text-navy uppercase dark:text-snow"
                        >
                            <Link :href="tryout.url!" class="hover:text-sky">{{
                                tryout.title
                            }}</Link>
                        </h2>
                        <span
                            class="rounded-md bg-navy px-2 py-1 text-xs font-bold text-snow"
                            >{{ tryout.division }}</span
                        >
                    </div>
                    <dl class="space-y-1 text-sm text-muted-foreground">
                        <div class="flex items-center gap-2">
                            <CalendarDays class="size-4 shrink-0 text-sky" />
                            <dd>{{ formatDateTime(tryout.event_at) }}</dd>
                        </div>
                        <div
                            v-if="tryout.location"
                            class="flex items-center gap-2"
                        >
                            <MapPin class="size-4 shrink-0 text-sky" />
                            <dd>{{ tryout.location }}</dd>
                        </div>
                    </dl>
                    <RegistrationStateBadge
                        :state="tryout.registration_state"
                        :spots-remaining="tryout.spots_remaining"
                        class="w-fit"
                    />
                    <div class="mt-auto flex flex-wrap gap-2 pt-2">
                        <Button as-child variant="outline" size="sm">
                            <Link :href="tryout.url!">View details</Link>
                        </Button>
                        <Button
                            v-if="tryout.registration_state === 'open'"
                            as-child
                            size="sm"
                            class="bg-sky font-semibold text-navy hover:bg-sky-dark hover:text-white"
                        >
                            <Link :href="tryout.register_url!">Register</Link>
                        </Button>
                    </div>
                </div>
            </li>
        </ul>
    </section>
</template>
