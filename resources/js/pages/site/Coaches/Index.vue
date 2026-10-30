<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Mail } from '@lucide/vue';
import { ref } from 'vue';
import PageHero from '@/components/site/PageHero.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogDescription,
    DialogHeader,
    DialogScrollContent,
    DialogTitle,
} from '@/components/ui/dialog';
import { contact } from '@/routes';
import type { Coach } from '@/types/teams';

defineProps<{
    coaches: Coach[];
}>();

const selectedCoach = ref<Coach | null>(null);
const dialogOpen = ref(false);

function openCoach(coach: Coach) {
    selectedCoach.value = coach;
    dialogOpen.value = true;
}

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

        <ul
            v-else
            class="grid grid-cols-2 gap-4 sm:grid-cols-3 sm:gap-6 lg:grid-cols-4"
        >
            <li v-for="coach in coaches" :key="coach.id">
                <button
                    type="button"
                    class="group flex size-full cursor-pointer flex-col items-center gap-3 rounded-lg border bg-card p-5 text-center shadow-sm transition hover:-translate-y-0.5 hover:border-sky hover:shadow-md focus-visible:ring-2 focus-visible:ring-sky focus-visible:outline-none sm:p-6"
                    :aria-label="`View ${coach.name}'s profile`"
                    @click="openCoach(coach)"
                >
                    <Avatar
                        class="size-24 ring-4 ring-sky/30 transition group-hover:ring-sky sm:size-32"
                    >
                        <AvatarImage
                            v-if="coach.photo_url"
                            :src="coach.photo_url"
                            :alt="`${coach.name} headshot`"
                            class="object-cover object-[center_20%]"
                            loading="lazy"
                        />
                        <AvatarFallback
                            class="bg-navy font-display text-3xl font-bold text-snow sm:text-4xl"
                        >
                            {{ initials(coach.name) }}
                        </AvatarFallback>
                    </Avatar>
                    <span class="grid gap-1">
                        <span
                            class="font-display text-xl font-bold tracking-wide text-navy uppercase sm:text-2xl dark:text-snow"
                        >
                            {{ coach.name }}
                        </span>
                        <span
                            v-if="coach.title"
                            class="text-xs font-semibold tracking-wide text-sky uppercase sm:text-sm"
                        >
                            {{ coach.title }}
                        </span>
                    </span>
                    <span
                        class="mt-auto text-xs font-medium text-muted-foreground group-hover:text-sky-dark"
                    >
                        View profile
                    </span>
                </button>
            </li>
        </ul>
    </section>

    <Dialog v-model:open="dialogOpen">
        <DialogScrollContent v-if="selectedCoach" class="sm:max-w-xl">
            <DialogHeader
                class="flex-col items-center gap-4 text-center sm:flex-row sm:text-left"
            >
                <Avatar class="size-28 shrink-0 ring-4 ring-sky/30">
                    <AvatarImage
                        v-if="selectedCoach.photo_url"
                        :src="selectedCoach.photo_url"
                        :alt="`${selectedCoach.name} headshot`"
                        class="object-cover object-[center_20%]"
                    />
                    <AvatarFallback
                        class="bg-navy font-display text-4xl font-bold text-snow"
                    >
                        {{ initials(selectedCoach.name) }}
                    </AvatarFallback>
                </Avatar>
                <div class="grid gap-1">
                    <DialogTitle
                        class="font-display text-3xl font-bold tracking-wide text-navy uppercase dark:text-snow"
                    >
                        {{ selectedCoach.name }}
                    </DialogTitle>
                    <DialogDescription
                        class="text-sm font-semibold tracking-wide text-sky uppercase"
                    >
                        {{ selectedCoach.title ?? 'Eagles coaching staff' }}
                    </DialogDescription>
                </div>
            </DialogHeader>
            <p
                v-if="selectedCoach.bio"
                class="text-sm leading-relaxed whitespace-pre-line text-muted-foreground"
            >
                {{ selectedCoach.bio }}
            </p>
            <p v-else class="text-sm text-muted-foreground italic">
                Bio coming soon.
            </p>
            <a
                v-if="selectedCoach.email"
                :href="`mailto:${selectedCoach.email}`"
                class="inline-flex items-center gap-2 text-sm font-medium text-sky-dark hover:underline"
            >
                <Mail class="size-4" /> {{ selectedCoach.email }}
            </a>
        </DialogScrollContent>
    </Dialog>
</template>
