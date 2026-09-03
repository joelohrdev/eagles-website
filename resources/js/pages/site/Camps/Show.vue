<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    ArrowLeft,
    CalendarDays,
    CheckCircle2,
    Clock,
    MapPin,
    Ticket,
    Users,
} from '@lucide/vue';
import { computed } from 'vue';
import RegistrationStateBadge from '@/components/site/RegistrationStateBadge.vue';
import ShareButtons from '@/components/site/ShareButtons.vue';
import YouTubeEmbed from '@/components/site/YouTubeEmbed.vue';
import { Button } from '@/components/ui/button';
import { formatDate, formatDateTime, formatTime, money } from '@/lib/format';
import { index as campsIndex } from '@/routes/camps';
import type { CampDetail } from '@/types/camps';

const props = defineProps<{
    camp: CampDetail;
    registered: boolean;
}>();

const page = usePage();
const shareUrl = computed(
    () => page.props.seo?.canonical_url ?? props.camp.url,
);
const shareText = computed(
    () => page.props.seo?.share_description ?? props.camp.name,
);

const endsSameDay = computed(
    () =>
        props.camp.ends_at !== null &&
        props.camp.starts_at.slice(0, 10) === props.camp.ends_at.slice(0, 10),
);

const registrationNote = computed(() => {
    switch (props.camp.registration_state) {
        case 'open':
            return props.camp.registration_closes_at
                ? `Registration closes ${formatDateTime(props.camp.registration_closes_at)}.`
                : 'Registration is open.';
        case 'upcoming':
            return props.camp.registration_opens_at
                ? `Registration opens ${formatDateTime(props.camp.registration_opens_at)}.`
                : 'Registration opens soon.';
        case 'full':
            return 'This camp is full. Contact us to be added to the waitlist.';
        default:
            return 'Registration for this camp has closed.';
    }
});
</script>

<template>
    <section class="relative overflow-hidden bg-navy text-snow">
        <img
            v-if="camp.image_url"
            :src="camp.image_url"
            alt=""
            class="absolute inset-0 size-full object-cover opacity-30"
            fetchpriority="high"
        />
        <div
            class="absolute inset-0 bg-gradient-to-br from-navy via-navy/90 to-sky/40"
            aria-hidden="true"
        />
        <div class="relative container-site py-12 md:py-16">
            <Link
                :href="campsIndex()"
                class="inline-flex items-center gap-1 text-sm text-stone hover:text-white"
            >
                <ArrowLeft class="size-4" /> All camps
            </Link>
            <p
                class="mt-4 text-sm font-semibold tracking-widest text-sky uppercase"
            >
                Camp
            </p>
            <h1
                class="font-display text-4xl font-bold tracking-wide uppercase sm:text-5xl"
            >
                {{ camp.name }}
            </h1>
            <p class="mt-3 text-lg text-stone">
                {{ formatDate(camp.starts_at)
                }}<template v-if="camp.location">
                    · {{ camp.location }}</template
                >
            </p>
            <div class="mt-5">
                <RegistrationStateBadge
                    :state="camp.registration_state"
                    :spots-remaining="camp.spots_remaining"
                />
            </div>
        </div>
    </section>

    <section class="container-site grid gap-10 py-12 md:py-16 lg:grid-cols-3">
        <div class="space-y-8 lg:col-span-2">
            <div
                v-if="registered"
                class="flex items-start gap-3 rounded-lg border border-sky/40 bg-accent p-5 text-navy"
                role="status"
            >
                <CheckCircle2 class="mt-0.5 size-6 shrink-0 text-sky-dark" />
                <div>
                    <p class="font-semibold">You're registered!</p>
                    <p class="text-sm">
                        A confirmation email is on its way. We'll see you at
                        {{ camp.name }}.
                    </p>
                </div>
            </div>

            <dl class="grid gap-4 sm:grid-cols-2">
                <div class="rounded-lg border bg-card p-4">
                    <dt
                        class="flex items-center gap-2 text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                    >
                        <CalendarDays class="size-4 text-sky" /> Date
                    </dt>
                    <dd class="mt-1 font-medium">
                        {{ formatDate(camp.starts_at) }}
                        <template v-if="camp.ends_at && !endsSameDay">
                            – {{ formatDate(camp.ends_at) }}</template
                        >
                    </dd>
                </div>
                <div class="rounded-lg border bg-card p-4">
                    <dt
                        class="flex items-center gap-2 text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                    >
                        <Clock class="size-4 text-sky" /> Time
                    </dt>
                    <dd class="mt-1 font-medium">
                        {{ formatTime(camp.starts_at)
                        }}<template v-if="camp.ends_at && endsSameDay">
                            – {{ formatTime(camp.ends_at) }}</template
                        >
                    </dd>
                </div>
                <div v-if="camp.location" class="rounded-lg border bg-card p-4">
                    <dt
                        class="flex items-center gap-2 text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                    >
                        <MapPin class="size-4 text-sky" /> Location
                    </dt>
                    <dd class="mt-1 font-medium">{{ camp.location }}</dd>
                </div>
                <div
                    v-if="camp.age_range"
                    class="rounded-lg border bg-card p-4"
                >
                    <dt
                        class="flex items-center gap-2 text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                    >
                        <Users class="size-4 text-sky" /> Ages
                    </dt>
                    <dd class="mt-1 font-medium">{{ camp.age_range }}</dd>
                </div>
            </dl>

            <div
                v-if="camp.description"
                class="prose max-w-none text-foreground"
            >
                <h2
                    class="font-display text-2xl font-bold tracking-wide text-navy uppercase dark:text-snow"
                >
                    About this camp
                </h2>
                <p class="mt-3 whitespace-pre-line text-muted-foreground">
                    {{ camp.description }}
                </p>
            </div>

            <YouTubeEmbed
                v-if="camp.youtube_url"
                :url="camp.youtube_url"
                :title="camp.name"
            />

            <ShareButtons
                :url="shareUrl"
                :title="camp.name"
                :text="shareText"
            />
        </div>

        <aside class="lg:col-span-1">
            <div class="sticky top-24 rounded-lg border bg-card p-6 shadow-sm">
                <p
                    class="flex items-center gap-2 text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                >
                    <Ticket class="size-4 text-sky" /> Registration
                </p>
                <p
                    class="mt-2 font-display text-4xl font-bold text-navy dark:text-snow"
                >
                    {{ camp.is_free ? 'Free' : money(camp.price) }}
                </p>
                <p class="mt-1 text-sm text-muted-foreground">
                    <template v-if="camp.spots_remaining !== null"
                        >{{ camp.spots_remaining }} of {{ camp.capacity }} spots
                        remaining · </template
                    >per player
                </p>
                <p class="mt-4 text-sm text-muted-foreground">
                    {{ registrationNote }}
                </p>
                <Button
                    v-if="camp.registration_state === 'open'"
                    as-child
                    size="lg"
                    class="mt-5 w-full bg-sky font-semibold text-navy hover:bg-sky-dark hover:text-white"
                >
                    <Link :href="camp.register_url">Register now</Link>
                </Button>
                <Button v-else size="lg" class="mt-5 w-full" disabled>
                    {{
                        camp.registration_state === 'upcoming'
                            ? 'Opens soon'
                            : camp.registration_state === 'full'
                              ? 'Full'
                              : 'Closed'
                    }}
                </Button>
                <p
                    v-if="!camp.is_free && camp.registration_state === 'open'"
                    class="mt-3 text-center text-xs text-muted-foreground"
                >
                    Secure payment by Stripe.
                </p>
            </div>
        </aside>
    </section>
</template>
