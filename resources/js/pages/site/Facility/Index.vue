<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight, MapPin } from '@lucide/vue';
import { computed, ref } from 'vue';
import PageHero from '@/components/site/PageHero.vue';
import SectionHeading from '@/components/site/SectionHeading.vue';
import YouTubeEmbed from '@/components/site/YouTubeEmbed.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogTitle,
} from '@/components/ui/dialog';
import { contact } from '@/routes';
import type { FacilityPhoto } from '@/types/teams';

const props = defineProps<{
    facility: {
        facility_heading: string | null;
        facility_description: string | null;
        facility_address: string | null;
        facility_youtube_url: string | null;
    };
    photos: FacilityPhoto[];
}>();

const open = ref(false);
const activeIndex = ref(0);

const activePhoto = computed(() => props.photos[activeIndex.value] ?? null);

function show(index: number) {
    activeIndex.value = index;
    open.value = true;
}

function step(direction: -1 | 1) {
    const count = props.photos.length;
    activeIndex.value = (activeIndex.value + direction + count) % count;
}

const mapsUrl = computed(() =>
    props.facility.facility_address
        ? `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(props.facility.facility_address)}`
        : null,
);
</script>

<template>
    <PageHero
        eyebrow="Eagles Baseball Travel"
        :title="facility.facility_heading || 'Our Facility'"
        :image-url="photos[0]?.image_url ?? null"
        size="large"
    />

    <section class="container-site grid gap-10 py-12 md:grid-cols-5 md:py-16">
        <div class="md:col-span-3">
            <SectionHeading
                eyebrow="Where we train"
                title="Built for development"
            />
            <p
                class="mt-6 text-lg leading-relaxed whitespace-pre-line text-muted-foreground"
            >
                {{ facility.facility_description }}
            </p>
        </div>
        <aside class="md:col-span-2">
            <div class="rounded-lg border bg-card p-6 shadow-sm">
                <h3
                    class="font-display text-xl font-bold tracking-wide text-navy uppercase dark:text-snow"
                >
                    Visit us
                </h3>
                <p
                    v-if="facility.facility_address"
                    class="mt-3 flex items-start gap-2 text-muted-foreground"
                >
                    <MapPin class="mt-0.5 size-5 shrink-0 text-sky" />
                    <span class="whitespace-pre-line">{{
                        facility.facility_address
                    }}</span>
                </p>
                <p v-else class="mt-3 text-muted-foreground">
                    Address and hours coming soon.
                </p>
                <div class="mt-5 flex flex-wrap gap-3">
                    <Button
                        v-if="mapsUrl"
                        as-child
                        class="bg-navy text-snow hover:bg-navy-light"
                    >
                        <a :href="mapsUrl" target="_blank" rel="noopener"
                            >Get directions</a
                        >
                    </Button>
                    <Button as-child variant="outline">
                        <Link :href="contact()">Ask a question</Link>
                    </Button>
                </div>
            </div>
        </aside>
    </section>

    <section
        v-if="facility.facility_youtube_url"
        class="container-site pb-12 md:pb-16"
    >
        <YouTubeEmbed
            :url="facility.facility_youtube_url"
            title="Facility tour"
        />
    </section>

    <section class="bg-muted/60">
        <div class="container-site py-12 md:py-16">
            <SectionHeading eyebrow="Gallery" title="Take a look inside" />

            <p v-if="photos.length === 0" class="mt-6 text-muted-foreground">
                Photos of the facility are on the way.
            </p>

            <div
                v-else
                class="mt-8 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4"
            >
                <button
                    v-for="(photo, i) in photos"
                    :key="photo.id"
                    type="button"
                    class="group relative overflow-hidden rounded-lg bg-muted focus:ring-2 focus:ring-sky focus:outline-none"
                    :aria-label="
                        photo.caption
                            ? `View photo: ${photo.caption}`
                            : `View photo ${i + 1}`
                    "
                    @click="show(i)"
                >
                    <img
                        :src="photo.thumbnail_url"
                        :alt="photo.caption ?? ''"
                        class="aspect-[3/2] w-full object-cover transition-transform duration-300 group-hover:scale-105"
                        loading="lazy"
                    />
                    <span
                        v-if="photo.caption"
                        class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-navy/80 to-transparent p-2 text-left text-xs text-snow"
                    >
                        {{ photo.caption }}
                    </span>
                </button>
            </div>
        </div>
    </section>

    <Dialog v-model:open="open">
        <DialogContent
            class="max-w-4xl border-none bg-navy p-2 text-snow sm:p-3"
        >
            <DialogTitle class="sr-only">Facility photo</DialogTitle>
            <DialogDescription class="sr-only">{{
                activePhoto?.caption ?? 'Facility photo'
            }}</DialogDescription>
            <div v-if="activePhoto" class="relative">
                <img
                    :src="activePhoto.image_url"
                    :alt="activePhoto.caption ?? ''"
                    class="max-h-[80vh] w-full rounded object-contain"
                />
                <p
                    v-if="activePhoto.caption"
                    class="mt-2 text-center text-sm text-stone"
                >
                    {{ activePhoto.caption }}
                </p>
                <template v-if="photos.length > 1">
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        class="absolute top-1/2 left-1 -translate-y-1/2 bg-navy/60 text-snow hover:bg-navy"
                        aria-label="Previous photo"
                        @click="step(-1)"
                    >
                        <ChevronLeft class="size-6" />
                    </Button>
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        class="absolute top-1/2 right-1 -translate-y-1/2 bg-navy/60 text-snow hover:bg-navy"
                        aria-label="Next photo"
                        @click="step(1)"
                    >
                        <ChevronRight class="size-6" />
                    </Button>
                </template>
            </div>
        </DialogContent>
    </Dialog>
</template>
