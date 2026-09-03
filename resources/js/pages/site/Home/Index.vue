<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    CalendarDays,
    ChevronDown,
    Mail,
    Phone,
    Trophy,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import PageHero from '@/components/site/PageHero.vue';
import RegistrationStateBadge from '@/components/site/RegistrationStateBadge.vue';
import SectionHeading from '@/components/site/SectionHeading.vue';
import YouTubeEmbed from '@/components/site/YouTubeEmbed.vue';
import { Button } from '@/components/ui/button';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import { formatDate, formatDateTime, money } from '@/lib/format';
import { contact } from '@/routes';
import { index as campsIndex, show as campShow } from '@/routes/camps';
import { index as tryoutsIndex, show as tryoutShow } from '@/routes/tryouts';
import type { RegistrationState } from '@/types';

type Offering = { title: string; description: string };
type Faq = { question: string; answer: string };
type HomeTryout = {
    id: number;
    slug: string;
    title: string;
    division: string;
    location: string | null;
    event_at: string;
    registration_state: RegistrationState;
    spots_remaining: number | null;
};
type HomeCamp = {
    id: number;
    slug: string;
    name: string;
    location: string | null;
    age_range: string | null;
    starts_at: string;
    ends_at: string | null;
    price: number;
    image_thumbnail_url: string | null;
    registration_state: RegistrationState;
    spots_remaining: number | null;
};

defineProps<{
    home: {
        home_hero_headline: string;
        home_hero_subheadline: string | null;
        home_hero_cta_label: string | null;
        home_hero_cta_url: string | null;
        home_hero_secondary_cta_label: string | null;
        home_hero_secondary_cta_url: string | null;
        home_hero_image_url: string | null;
        home_intro: string | null;
        home_offerings: Offering[];
        home_about_heading: string | null;
        home_about_body: string | null;
        home_about_image_url: string | null;
        home_youtube_url: string | null;
    };
    faqs: Faq[];
    tryouts: HomeTryout[];
    camps: HomeCamp[];
}>();

const page = usePage();
const site = computed(() => page.props.site);

const openFaq = ref<number | null>(null);
</script>

<template>
    <div>
        <PageHero
            :title="home.home_hero_headline"
            :description="home.home_hero_subheadline"
            :image-url="home.home_hero_image_url"
            eyebrow="Eagles Baseball Travel"
            size="large"
        >
            <Button
                v-if="home.home_hero_cta_label && home.home_hero_cta_url"
                as-child
                size="lg"
                class="bg-sky font-semibold text-navy hover:bg-sky-dark hover:text-white"
            >
                <Link :href="home.home_hero_cta_url">{{
                    home.home_hero_cta_label
                }}</Link>
            </Button>
            <Button
                v-if="
                    home.home_hero_secondary_cta_label &&
                    home.home_hero_secondary_cta_url
                "
                as-child
                size="lg"
                variant="outline"
                class="border-snow/40 bg-transparent text-snow hover:bg-snow/10 hover:text-white"
            >
                <Link :href="home.home_hero_secondary_cta_url">{{
                    home.home_hero_secondary_cta_label
                }}</Link>
            </Button>
        </PageHero>

        <section v-if="home.home_intro" class="border-b bg-accent/60">
            <div class="container-site py-8 md:py-10">
                <p
                    class="mx-auto max-w-3xl text-center text-lg leading-relaxed text-navy md:text-xl dark:text-snow"
                >
                    {{ home.home_intro }}
                </p>
            </div>
        </section>

        <section
            v-if="home.home_offerings?.length"
            class="container-site py-12 md:py-16"
        >
            <SectionHeading
                eyebrow="What we offer"
                title="Built for players who want more"
                align="center"
            />
            <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                <div
                    v-for="offering in home.home_offerings"
                    :key="offering.title"
                    class="rounded-lg border bg-card p-6 shadow-sm"
                >
                    <h3
                        class="font-display text-xl font-semibold tracking-wide uppercase"
                    >
                        {{ offering.title }}
                    </h3>
                    <p class="mt-2 text-sm text-muted-foreground">
                        {{ offering.description }}
                    </p>
                </div>
            </div>
        </section>

        <section v-if="tryouts.length" class="bg-navy text-snow">
            <div class="container-site py-12 md:py-16">
                <div class="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <p
                            class="mb-1 text-sm font-semibold tracking-widest text-sky uppercase"
                        >
                            Upcoming tryouts
                        </p>
                        <h2
                            class="font-display text-3xl font-bold tracking-wide uppercase md:text-4xl"
                        >
                            Earn your spot
                        </h2>
                    </div>
                    <Button
                        as-child
                        variant="outline"
                        class="border-snow/40 bg-transparent text-snow hover:bg-snow/10 hover:text-white"
                    >
                        <Link :href="tryoutsIndex()">All tryouts</Link>
                    </Button>
                </div>
                <div class="mt-8 grid gap-4 md:grid-cols-3">
                    <Link
                        v-for="tryout in tryouts"
                        :key="tryout.id"
                        :href="tryoutShow(tryout.slug)"
                        class="rounded-lg border border-navy-light bg-navy-light/60 p-5 transition-colors hover:border-sky"
                    >
                        <div class="flex items-start justify-between gap-2">
                            <span
                                class="rounded bg-sky px-2 py-0.5 text-xs font-bold text-navy"
                                >{{ tryout.division }}</span
                            >
                            <RegistrationStateBadge
                                :state="tryout.registration_state"
                                :spots-remaining="tryout.spots_remaining"
                            />
                        </div>
                        <h3
                            class="mt-3 font-display text-2xl font-semibold tracking-wide uppercase"
                        >
                            {{ tryout.title }}
                        </h3>
                        <p class="mt-1 text-sm text-stone">
                            {{ formatDateTime(tryout.event_at) }}
                        </p>
                        <p v-if="tryout.location" class="text-sm text-stone">
                            {{ tryout.location }}
                        </p>
                    </Link>
                </div>
            </div>
        </section>

        <section v-if="camps.length" class="container-site py-12 md:py-16">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <SectionHeading
                    eyebrow="Camps & clinics"
                    title="Train year-round"
                />
                <Button as-child variant="outline">
                    <Link :href="campsIndex()">All camps</Link>
                </Button>
            </div>
            <div class="mt-8 grid gap-6 md:grid-cols-3">
                <Link
                    v-for="camp in camps"
                    :key="camp.id"
                    :href="campShow(camp.slug)"
                    class="overflow-hidden rounded-lg border bg-card shadow-sm transition-shadow hover:shadow-md"
                >
                    <img
                        v-if="camp.image_thumbnail_url"
                        :src="camp.image_thumbnail_url"
                        :alt="camp.name"
                        loading="lazy"
                        class="aspect-video w-full object-cover"
                    />
                    <div
                        v-else
                        class="flex aspect-video w-full items-center justify-center bg-navy text-sky"
                    >
                        <CalendarDays class="size-10" />
                    </div>
                    <div class="p-5">
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-sm font-semibold text-sky">{{
                                camp.price === 0 ? 'Free' : money(camp.price)
                            }}</span>
                            <RegistrationStateBadge
                                :state="camp.registration_state"
                                :spots-remaining="camp.spots_remaining"
                            />
                        </div>
                        <h3
                            class="mt-2 font-display text-xl font-semibold tracking-wide uppercase"
                        >
                            {{ camp.name }}
                        </h3>
                        <p class="mt-1 text-sm text-muted-foreground">
                            {{ formatDate(camp.starts_at)
                            }}<template v-if="camp.age_range">
                                · {{ camp.age_range }}</template
                            >
                        </p>
                        <p
                            v-if="camp.location"
                            class="text-sm text-muted-foreground"
                        >
                            {{ camp.location }}
                        </p>
                    </div>
                </Link>
            </div>
        </section>

        <section
            v-if="home.home_about_heading || home.home_about_body"
            class="border-t bg-muted/40"
        >
            <div
                class="container-site grid items-center gap-10 py-12 md:grid-cols-2 md:py-16"
            >
                <div>
                    <SectionHeading
                        eyebrow="About"
                        :title="home.home_about_heading ?? 'About the Eagles'"
                    />
                    <p
                        class="mt-4 leading-relaxed whitespace-pre-line text-muted-foreground"
                    >
                        {{ home.home_about_body }}
                    </p>
                    <Button
                        as-child
                        class="mt-6 bg-sky font-semibold text-navy hover:bg-sky-dark hover:text-white"
                    >
                        <Link :href="contact()">Get in touch</Link>
                    </Button>
                </div>
                <div>
                    <YouTubeEmbed
                        v-if="home.home_youtube_url"
                        :url="home.home_youtube_url"
                        title="About the Eagles"
                    />
                    <img
                        v-else-if="home.home_about_image_url"
                        :src="home.home_about_image_url"
                        alt=""
                        loading="lazy"
                        class="aspect-[4/3] w-full rounded-lg object-cover shadow-md"
                    />
                    <div
                        v-else
                        class="flex aspect-[4/3] w-full items-center justify-center rounded-lg bg-navy text-sky"
                    >
                        <Trophy class="size-16" />
                    </div>
                </div>
            </div>
        </section>

        <section v-if="faqs.length" class="container-site py-12 md:py-16">
            <SectionHeading
                eyebrow="FAQ"
                title="Common questions"
                align="center"
            />
            <div
                class="mx-auto mt-8 max-w-3xl divide-y rounded-lg border bg-card"
            >
                <Collapsible
                    v-for="(faq, index) in faqs"
                    :key="index"
                    :open="openFaq === index"
                    @update:open="
                        (open: boolean) => (openFaq = open ? index : null)
                    "
                >
                    <CollapsibleTrigger
                        class="flex w-full items-center justify-between gap-4 px-5 py-4 text-left font-medium"
                    >
                        <span>{{ faq.question }}</span>
                        <ChevronDown
                            class="size-4 shrink-0 transition-transform"
                            :class="{ 'rotate-180': openFaq === index }"
                        />
                    </CollapsibleTrigger>
                    <CollapsibleContent
                        class="px-5 pb-4 text-sm whitespace-pre-line text-muted-foreground"
                    >
                        {{ faq.answer }}
                    </CollapsibleContent>
                </Collapsible>
            </div>
        </section>

        <section class="bg-navy text-snow">
            <div
                class="container-site flex flex-col items-center gap-6 py-12 text-center md:flex-row md:justify-between md:text-left"
            >
                <div>
                    <h2
                        class="font-display text-3xl font-bold tracking-wide uppercase"
                    >
                        Ready to join the Eagles?
                    </h2>
                    <p class="mt-2 text-stone">
                        Questions about teams, tryouts, or camps? We'd love to
                        hear from you.
                    </p>
                    <div
                        class="mt-3 flex flex-wrap justify-center gap-4 text-sm md:justify-start"
                    >
                        <a
                            v-if="site?.phone"
                            :href="`tel:${site.phone.replace(/[^0-9+]/g, '')}`"
                            class="inline-flex items-center gap-2 hover:text-sky"
                        >
                            <Phone class="size-4" /> {{ site.phone }}
                        </a>
                        <a
                            v-if="site?.email"
                            :href="`mailto:${site.email}`"
                            class="inline-flex items-center gap-2 hover:text-sky"
                        >
                            <Mail class="size-4" /> {{ site.email }}
                        </a>
                    </div>
                </div>
                <Button
                    as-child
                    size="lg"
                    class="bg-sky font-semibold text-navy hover:bg-sky-dark hover:text-white"
                >
                    <Link :href="contact()">Contact us</Link>
                </Button>
            </div>
        </section>
    </div>
</template>
