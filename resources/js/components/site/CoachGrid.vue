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

const narrow = computed(() => props.layout === 'narrow');

/**
 * "narrow" (the camp page's main column) uses compact cards, smaller photos,
 * less padding and no "View profile" line, so four fit on a row without
 * a lot of empty space.
 */
const classes = computed(() =>
    narrow.value
        ? {
              grid: 'grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4',
              card: 'gap-2 p-3 sm:p-4',
              avatar: 'size-20 ring-2 sm:size-24',
              initials: 'text-2xl sm:text-3xl',
              name: 'text-lg leading-tight',
          }
        : {
              grid: 'grid grid-cols-2 gap-4 sm:grid-cols-3 sm:gap-6 lg:grid-cols-4',
              card: 'gap-3 p-5 sm:p-6',
              avatar: 'size-24 ring-4 sm:size-32',
              initials: 'text-3xl sm:text-4xl',
              name: 'text-xl sm:text-2xl',
          },
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
        <ul :class="classes.grid">
            <li v-for="coach in coaches" :key="coach.id">
                <button
                    type="button"
                    class="group flex size-full cursor-pointer flex-col items-center rounded-lg border bg-card text-center shadow-sm transition hover:-translate-y-0.5 hover:border-sky hover:shadow-md focus-visible:ring-2 focus-visible:ring-sky focus-visible:outline-none"
                    :class="classes.card"
                    :aria-label="`View ${coach.name}'s profile`"
                    @click="openCoach(coach)"
                >
                    <Avatar
                        class="ring-sky/30 transition group-hover:ring-sky"
                        :class="classes.avatar"
                    >
                        <AvatarImage
                            v-if="coach.photo_url"
                            :src="coach.photo_url"
                            :alt="`${coach.name} headshot`"
                            class="object-cover object-[center_20%]"
                            loading="lazy"
                        />
                        <AvatarFallback
                            class="bg-navy font-display font-bold text-snow"
                            :class="classes.initials"
                        >
                            {{ initials(coach.name) }}
                        </AvatarFallback>
                    </Avatar>
                    <span class="grid gap-1">
                        <span
                            class="font-display font-bold tracking-wide text-navy uppercase dark:text-snow"
                            :class="classes.name"
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
                        v-if="!narrow"
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
