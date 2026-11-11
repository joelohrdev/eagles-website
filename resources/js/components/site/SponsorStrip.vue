<script setup lang="ts">
import { computed } from 'vue';
import type { HomeSponsor } from '@/types/sponsors';

/** Up to this many logos sit still in a centred row; any more and they scroll. */
const STATIC_LIMIT = 4;

/** Seconds each logo spends crossing the strip, so speed stays even as the list grows. */
const SECONDS_PER_LOGO = 4;

const props = defineProps<{
    sponsors: HomeSponsor[];
}>();

const rotates = computed(() => props.sponsors.length > STATIC_LIMIT);

const marqueeDuration = computed(
    () => `${props.sponsors.length * SECONDS_PER_LOGO}s`,
);
</script>

<template>
    <div>
        <ul
            v-if="!rotates"
            class="flex flex-wrap items-center justify-center gap-6 md:gap-10"
        >
            <li v-for="sponsor in sponsors" :key="sponsor.id">
                <component
                    :is="sponsor.website_url ? 'a' : 'div'"
                    v-bind="
                        sponsor.website_url
                            ? {
                                  href: sponsor.website_url,
                                  target: '_blank',
                                  rel: 'sponsored noopener noreferrer',
                              }
                            : {}
                    "
                    class="flex h-24 w-44 items-center justify-center rounded-lg border bg-white p-4 shadow-sm transition hover:shadow-md md:w-52"
                >
                    <img
                        :src="sponsor.logo_url"
                        :alt="sponsor.name"
                        loading="lazy"
                        class="max-h-full max-w-full object-contain"
                    />
                </component>
            </li>
        </ul>

        <div
            v-else
            class="group overflow-hidden mask-x-from-90% mask-x-to-100% motion-reduce:overflow-x-auto motion-reduce:mask-none"
        >
            <div
                class="flex w-max animate-marquee group-focus-within:[animation-play-state:paused] group-hover:[animation-play-state:paused] motion-reduce:animate-none"
                :style="{ '--marquee-duration': marqueeDuration }"
            >
                <ul
                    v-for="copy in 2"
                    :key="copy"
                    class="flex shrink-0 items-center gap-6 py-2 pr-6 md:gap-10 md:pr-10"
                    :class="{ 'motion-reduce:hidden': copy === 2 }"
                    :aria-hidden="copy === 2 ? 'true' : undefined"
                >
                    <li v-for="sponsor in sponsors" :key="sponsor.id">
                        <component
                            :is="sponsor.website_url ? 'a' : 'div'"
                            v-bind="
                                sponsor.website_url
                                    ? {
                                          href: sponsor.website_url,
                                          target: '_blank',
                                          rel: 'sponsored noopener noreferrer',
                                          tabindex: copy === 2 ? -1 : undefined,
                                      }
                                    : {}
                            "
                            class="flex h-24 w-44 items-center justify-center rounded-lg border bg-white p-4 shadow-sm transition hover:shadow-md md:w-52"
                        >
                            <img
                                :src="sponsor.logo_url"
                                :alt="copy === 2 ? '' : sponsor.name"
                                loading="lazy"
                                class="max-h-full max-w-full object-contain"
                            />
                        </component>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</template>
