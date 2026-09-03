<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import PageHero from '@/components/site/PageHero.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { contact } from '@/routes';
import { index as tryoutsIndex } from '@/routes/tryouts';
import type { Team } from '@/types/teams';

const props = defineProps<{
    teams: Team[];
}>();

const page = usePage();
const tryoutsOpen = computed(() => page.props.tryoutsOpen);

const divisions = computed(() => {
    const groups = new Map<string, Team[]>();

    for (const team of props.teams) {
        const list = groups.get(team.division) ?? [];
        list.push(team);
        groups.set(team.division, list);
    }

    return Array.from(groups.entries()).map(([division, teams]) => ({
        division,
        teams,
    }));
});
</script>

<template>
    <PageHero
        eyebrow="Eagles Baseball Travel"
        title="Our Teams"
        description="Competitive travel baseball teams for every age group, each led by experienced coaches focused on player development."
    >
        <Button
            v-if="tryoutsOpen"
            as-child
            class="bg-sky font-semibold text-navy hover:bg-sky-dark hover:text-white"
        >
            <Link :href="tryoutsIndex()">See upcoming tryouts</Link>
        </Button>
    </PageHero>

    <section class="container-site py-12 md:py-16">
        <div
            v-if="teams.length === 0"
            class="rounded-lg border border-dashed p-12 text-center"
        >
            <h2
                class="font-display text-2xl font-bold tracking-wide text-navy uppercase dark:text-snow"
            >
                Rosters coming soon
            </h2>
            <p class="mx-auto mt-2 max-w-md text-muted-foreground">
                We're finalizing this season's teams. Check back soon, or reach
                out to learn about upcoming tryouts.
            </p>
            <Button
                as-child
                class="mt-6 bg-sky font-semibold text-navy hover:bg-sky-dark hover:text-white"
            >
                <Link :href="contact()">Contact us</Link>
            </Button>
        </div>

        <div v-else class="space-y-14">
            <div v-for="group in divisions" :key="group.division">
                <div class="mb-6 flex items-center gap-4">
                    <h2
                        class="font-display text-3xl font-bold tracking-wide text-navy uppercase dark:text-snow"
                    >
                        {{ group.division }}
                    </h2>
                    <div class="h-px flex-1 bg-border" aria-hidden="true" />
                </div>
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <article
                        v-for="team in group.teams"
                        :key="team.id"
                        class="flex flex-col overflow-hidden rounded-lg border bg-card shadow-sm"
                    >
                        <img
                            v-if="team.photo_thumbnail_url"
                            :src="team.photo_thumbnail_url"
                            :alt="`${team.name} team photo`"
                            class="aspect-video w-full object-cover"
                            loading="lazy"
                        />
                        <div
                            v-else
                            class="flex aspect-video w-full items-center justify-center bg-navy text-snow"
                        >
                            <span
                                class="font-display text-4xl font-bold uppercase opacity-70"
                                >{{ team.division }}</span
                            >
                        </div>
                        <div class="flex flex-1 flex-col gap-3 p-5">
                            <div class="flex flex-wrap items-center gap-2">
                                <Badge class="bg-sky text-navy hover:bg-sky">{{
                                    team.division
                                }}</Badge>
                                <Badge v-if="team.season" variant="outline"
                                    >{{ team.season }} season</Badge
                                >
                            </div>
                            <h3
                                class="font-display text-2xl font-bold tracking-wide text-navy uppercase dark:text-snow"
                            >
                                {{ team.name }}
                            </h3>
                            <p
                                v-if="team.coach"
                                class="text-sm font-medium text-muted-foreground"
                            >
                                {{
                                    team.coach.title
                                        ? `${team.coach.title}: `
                                        : 'Coach: '
                                }}{{ team.coach.name }}
                            </p>
                            <p
                                v-if="team.description"
                                class="text-sm leading-relaxed text-muted-foreground"
                            >
                                {{ team.description }}
                            </p>
                        </div>
                    </article>
                </div>
            </div>
        </div>
    </section>

    <section class="bg-accent">
        <div
            class="container-site flex flex-col items-start justify-between gap-6 py-12 md:flex-row md:items-center"
        >
            <div>
                <h2
                    class="font-display text-3xl font-bold tracking-wide text-navy uppercase"
                >
                    Want to play for the Eagles?
                </h2>
                <p
                    v-if="tryoutsOpen"
                    class="mt-2 max-w-xl text-muted-foreground"
                >
                    Tryouts are held for every age group. Register online or
                    contact us with questions.
                </p>
                <p v-else class="mt-2 max-w-xl text-muted-foreground">
                    Tryouts are held for every age group. Contact us to hear
                    about the next round of dates.
                </p>
            </div>
            <div class="flex flex-wrap gap-3">
                <Button
                    v-if="tryoutsOpen"
                    as-child
                    class="bg-navy text-snow hover:bg-navy-light"
                >
                    <Link :href="tryoutsIndex()">View tryouts</Link>
                </Button>
                <Button
                    as-child
                    variant="outline"
                    class="border-navy text-navy hover:bg-navy hover:text-snow"
                >
                    <Link :href="contact()">Contact us</Link>
                </Button>
            </div>
        </div>
    </section>
</template>
