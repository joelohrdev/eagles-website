<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    ArrowLeft,
    CalendarDays,
    CheckCircle2,
    MapPin,
    Shield,
    Users,
} from '@lucide/vue';
import { computed } from 'vue';
import PageHero from '@/components/site/PageHero.vue';
import RegistrationStateBadge from '@/components/site/RegistrationStateBadge.vue';
import ShareButtons from '@/components/site/ShareButtons.vue';
import { Button } from '@/components/ui/button';
import { formatDate, formatDateTime, formatTime } from '@/lib/format';
import { contact } from '@/routes';
import { index as tryoutsIndex } from '@/routes/tryouts';
import type { Tryout } from '@/types/tryouts';

const props = defineProps<{
    tryout: Tryout;
    registered: boolean;
}>();

const page = usePage();
const shareText = computed(
    () =>
        `${props.tryout.title} — ${formatDateTime(props.tryout.event_at)}${props.tryout.location ? ` at ${props.tryout.location}` : ''}`,
);
const shareUrl = computed(
    () => page.props.seo?.canonical_url ?? props.tryout.url ?? '',
);

const registrationCopy = computed(() => {
    switch (props.tryout.registration_state) {
        case 'open':
            return props.tryout.registration_closes_at
                ? `Registration is open until ${formatDate(props.tryout.registration_closes_at)}.`
                : 'Registration is open.';
        case 'upcoming':
            return `Registration opens ${formatDateTime(props.tryout.registration_opens_at)}.`;
        case 'full':
            return 'This tryout is full. Contact us to be added to the waitlist.';
        default:
            return 'Registration for this tryout has closed.';
    }
});
</script>

<template>
    <PageHero
        :eyebrow="`${tryout.division} Division`"
        :title="tryout.title"
        :description="`${formatDateTime(tryout.event_at)}${tryout.location ? ` · ${tryout.location}` : ''}`"
        :image-url="tryout.image_url"
    >
        <Button
            v-if="tryout.registration_state === 'open'"
            as-child
            size="lg"
            class="bg-sky font-semibold text-navy hover:bg-sky-dark hover:text-white"
        >
            <Link :href="tryout.register_url!">Register now</Link>
        </Button>
        <Button
            as-child
            size="lg"
            variant="outline"
            class="border-snow/40 bg-transparent text-snow hover:bg-navy-light hover:text-white"
        >
            <Link :href="tryoutsIndex()"
                ><ArrowLeft class="size-4" /> All tryouts</Link
            >
        </Button>
    </PageHero>

    <section class="container-site py-12 md:py-16">
        <div
            v-if="registered"
            class="mb-10 flex items-start gap-3 rounded-lg border border-sky bg-accent p-5 text-navy"
            role="status"
        >
            <CheckCircle2 class="mt-0.5 size-6 shrink-0 text-sky-dark" />
            <div>
                <p class="font-display text-xl font-bold uppercase">
                    You're registered!
                </p>
                <p class="mt-1 text-sm">
                    A confirmation email is on its way. Please arrive 15–20
                    minutes early and bring your glove, bat, helmet, cleats, and
                    water.
                </p>
            </div>
        </div>

        <div class="grid gap-10 lg:grid-cols-3">
            <div class="space-y-8 lg:col-span-2">
                <dl class="grid gap-4 sm:grid-cols-2">
                    <div
                        class="flex items-start gap-3 rounded-lg border bg-card p-4"
                    >
                        <CalendarDays class="mt-0.5 size-5 shrink-0 text-sky" />
                        <div>
                            <dt
                                class="text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                            >
                                Date &amp; time
                            </dt>
                            <dd class="font-medium">
                                {{ formatDate(tryout.event_at) }}
                            </dd>
                            <dd class="text-sm text-muted-foreground">
                                {{ formatTime(tryout.event_at) }}
                            </dd>
                        </div>
                    </div>
                    <div
                        class="flex items-start gap-3 rounded-lg border bg-card p-4"
                    >
                        <MapPin class="mt-0.5 size-5 shrink-0 text-sky" />
                        <div>
                            <dt
                                class="text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                            >
                                Location
                            </dt>
                            <dd class="font-medium">
                                {{ tryout.location ?? 'To be announced' }}
                            </dd>
                        </div>
                    </div>
                    <div
                        class="flex items-start gap-3 rounded-lg border bg-card p-4"
                    >
                        <Shield class="mt-0.5 size-5 shrink-0 text-sky" />
                        <div>
                            <dt
                                class="text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                            >
                                Division
                            </dt>
                            <dd class="font-medium">{{ tryout.division }}</dd>
                        </div>
                    </div>
                    <div
                        class="flex items-start gap-3 rounded-lg border bg-card p-4"
                    >
                        <Users class="mt-0.5 size-5 shrink-0 text-sky" />
                        <div>
                            <dt
                                class="text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                            >
                                Spots
                            </dt>
                            <dd class="font-medium">
                                <template v-if="tryout.capacity"
                                    >{{ tryout.spots_remaining }} of
                                    {{ tryout.capacity }} remaining</template
                                >
                                <template v-else>Open enrollment</template>
                            </dd>
                        </div>
                    </div>
                </dl>

                <div
                    v-if="tryout.description"
                    class="prose dark:prose-invert max-w-none"
                >
                    <h2
                        class="font-display text-2xl font-bold tracking-wide text-navy uppercase dark:text-snow"
                    >
                        About this tryout
                    </h2>
                    <p class="mt-3 whitespace-pre-line text-muted-foreground">
                        {{ tryout.description }}
                    </p>
                </div>

                <ShareButtons
                    :url="shareUrl"
                    :title="tryout.title"
                    :text="shareText"
                />
            </div>

            <aside class="lg:sticky lg:top-24 lg:self-start">
                <div class="rounded-lg border bg-navy p-6 text-snow shadow-sm">
                    <RegistrationStateBadge
                        :state="tryout.registration_state"
                        :spots-remaining="tryout.spots_remaining"
                    />
                    <h2
                        class="mt-3 font-display text-2xl font-bold tracking-wide uppercase"
                    >
                        Registration
                    </h2>
                    <p class="mt-2 text-sm text-stone">
                        {{ registrationCopy }}
                    </p>
                    <p class="mt-2 text-sm text-stone">Registration is free.</p>
                    <Button
                        v-if="tryout.registration_state === 'open'"
                        as-child
                        size="lg"
                        class="mt-5 w-full bg-sky font-semibold text-navy hover:bg-sky-dark hover:text-white"
                    >
                        <Link :href="tryout.register_url!">Register now</Link>
                    </Button>
                    <Button
                        v-else
                        as-child
                        size="lg"
                        variant="outline"
                        class="mt-5 w-full border-snow/40 bg-transparent text-snow hover:bg-navy-light hover:text-white"
                    >
                        <Link :href="contact()">Contact us</Link>
                    </Button>
                </div>
            </aside>
        </div>
    </section>
</template>
