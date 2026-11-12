<script setup lang="ts">
import { Mail } from '@lucide/vue';
import { computed, ref } from 'vue';
import FormattedText from '@/components/site/FormattedText.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import {
    Dialog,
    DialogDescription,
    DialogHeader,
    DialogScrollContent,
    DialogTitle,
} from '@/components/ui/dialog';

/** What the grid needs from a coach: the coaching staff and camp coaches both fit. */
export type CoachCard = {
    id: number;
    name: string;
    title: string | null;
    bio: string | null;
    photo_url: string | null;
    email?: string | null;
};

/**
 * Headshot cards that open the coach's bio in a dialog. Used by the coaching
 * staff page and the coaches section on camp pages.
 */
const props = withDefaults(
    defineProps<{
        coaches: CoachCard[];
        /** Shown under the name in the dialog when a coach has no role. */
        fallbackTitle?: string;
        /**
         * "wide" fills a full-width page (up to 4 per row); "narrow" suits a
         * column like the camp page's main content (up to 3 per row).
         */
        layout?: 'wide' | 'narrow';
    }>(),
    { fallbackTitle: 'Eagles coaching staff', layout: 'wide' },
);

const gridClass = computed(() =>
    props.layout === 'narrow'
        ? 'grid grid-cols-2 gap-4 sm:gap-6 md:grid-cols-3'
        : 'grid grid-cols-2 gap-4 sm:grid-cols-3 sm:gap-6 lg:grid-cols-4',
);

/**
 * Split a role on slashes so it can wrap after each one: "Infield/Defense/
 * Hitting Instructor" has no spaces to break at and would overflow the card.
 */
function titleParts(title: string): string[] {
    return title.split(/(?<=\/)/);
}

const selectedCoach = ref<CoachCard | null>(null);
const dialogOpen = ref(false);

function openCoach(coach: CoachCard) {
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
    <div>
        <ul :class="gridClass">
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
                            class="text-[0.6875rem] leading-snug font-semibold tracking-wide text-balance break-words text-sky uppercase sm:text-xs"
                        >
                            <template
                                v-for="(part, i) in titleParts(coach.title)"
                                :key="i"
                                >{{ part }}<wbr
                            /></template>
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
                            <template
                                v-for="(part, i) in titleParts(
                                    selectedCoach.title ?? fallbackTitle,
                                )"
                                :key="i"
                                >{{ part }}<wbr
                            /></template>
                        </DialogDescription>
                    </div>
                </DialogHeader>
                <FormattedText
                    v-if="selectedCoach.bio"
                    :text="selectedCoach.bio"
                    class="text-sm leading-relaxed text-muted-foreground"
                />
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
    </div>
</template>
