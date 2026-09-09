<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { Mail, Phone } from '@lucide/vue';
import { computed } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import FacebookIcon from '@/components/site/icons/FacebookIcon.vue';
import InstagramIcon from '@/components/site/icons/InstagramIcon.vue';
import XIcon from '@/components/site/icons/XIcon.vue';
import YoutubeIcon from '@/components/site/icons/YoutubeIcon.vue';
import NavLink from '@/components/site/NavLink.vue';
import { home } from '@/routes';

const page = usePage();
const site = computed(() => page.props.site);
const nav = computed(() => page.props.navigation);
const settings = computed(() => nav.value?.settings);
const links = computed(() => nav.value?.menus.footer ?? []);
const bottomLinks = computed(() => nav.value?.menus.footer_bottom ?? []);

const orgName = computed(() => site.value?.org_name ?? page.props.name);

const hasSocials = computed(
    () =>
        !!(
            site.value?.social_facebook ||
            site.value?.social_instagram ||
            site.value?.social_twitter ||
            site.value?.social_youtube
        ),
);

const address = computed(() => {
    if (!site.value?.address_line1 && !site.value?.address_city) {
        return '';
    }

    return [
        site.value?.address_line1,
        [site.value?.address_city, site.value?.address_state]
            .filter(Boolean)
            .join(', '),
        site.value?.address_postal_code,
    ]
        .filter(Boolean)
        .join(' · ');
});

const copyright = computed(
    () =>
        settings.value?.footer_copyright ??
        `© ${new Date().getFullYear()} ${orgName.value}. All rights reserved.`,
);
</script>

<template>
    <footer class="bg-navy text-snow">
        <div
            class="container-site flex flex-col gap-8 py-10 md:flex-row md:items-start md:justify-between md:gap-12"
        >
            <div class="max-w-sm">
                <Link :href="home()" class="inline-flex items-center gap-3">
                    <AppLogoIcon class="h-9 w-auto" />
                    <span
                        class="font-display text-xl font-bold tracking-wide uppercase"
                    >
                        {{ orgName }}
                    </span>
                </Link>
                <p
                    v-if="settings?.footer_tagline"
                    class="mt-3 text-sm text-stone"
                >
                    {{ settings.footer_tagline }}
                </p>
                <div
                    v-if="settings?.footer_show_socials !== false && hasSocials"
                    class="mt-4 flex gap-3"
                >
                    <a
                        v-if="site?.social_facebook"
                        :href="site.social_facebook"
                        target="_blank"
                        rel="noopener"
                        aria-label="Facebook"
                        class="text-stone hover:text-sky"
                        ><FacebookIcon class="size-5"
                    /></a>
                    <a
                        v-if="site?.social_instagram"
                        :href="site.social_instagram"
                        target="_blank"
                        rel="noopener"
                        aria-label="Instagram"
                        class="text-stone hover:text-sky"
                        ><InstagramIcon class="size-5"
                    /></a>
                    <a
                        v-if="site?.social_twitter"
                        :href="site.social_twitter"
                        target="_blank"
                        rel="noopener"
                        aria-label="X"
                        class="text-stone hover:text-sky"
                        ><XIcon class="size-5"
                    /></a>
                    <a
                        v-if="site?.social_youtube"
                        :href="site.social_youtube"
                        target="_blank"
                        rel="noopener"
                        aria-label="YouTube"
                        class="text-stone hover:text-sky"
                        ><YoutubeIcon class="size-5"
                    /></a>
                </div>
            </div>

            <div class="flex flex-wrap gap-x-16 gap-y-8">
                <nav v-if="links.length" aria-label="Footer">
                    <h2
                        class="text-xs font-semibold tracking-widest text-sky uppercase"
                    >
                        {{ settings?.footer_links_heading ?? 'Explore' }}
                    </h2>
                    <ul
                        class="mt-3 grid grid-flow-col gap-x-10 gap-y-2 text-sm"
                        :style="{
                            gridTemplateRows: `repeat(${Math.ceil(links.length / 2)}, auto)`,
                        }"
                    >
                        <li v-for="link in links" :key="link.id ?? link.label">
                            <NavLink
                                :item="link"
                                class="text-stone hover:text-white"
                            />
                        </li>
                    </ul>
                </nav>

                <div
                    v-if="
                        settings?.footer_show_contact !== false &&
                        (site?.phone || site?.email || address)
                    "
                >
                    <h2
                        class="text-xs font-semibold tracking-widest text-sky uppercase"
                    >
                        {{ settings?.footer_contact_heading ?? 'Contact' }}
                    </h2>
                    <ul class="mt-3 space-y-2 text-sm">
                        <li v-if="site?.phone">
                            <a
                                :href="`tel:${site.phone.replace(/[^0-9+]/g, '')}`"
                                class="inline-flex items-center gap-2 text-stone hover:text-white"
                            >
                                <Phone class="size-4" /> {{ site.phone }}
                            </a>
                        </li>
                        <li v-if="site?.email">
                            <a
                                :href="`mailto:${site.email}`"
                                class="inline-flex items-center gap-2 text-stone hover:text-white"
                            >
                                <Mail class="size-4" /> {{ site.email }}
                            </a>
                        </li>
                        <li
                            v-if="
                                address &&
                                settings?.footer_show_address !== false
                            "
                            class="text-stone"
                        >
                            {{ address }}
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="border-t border-navy-light">
            <div
                class="container-site flex flex-col gap-2 py-4 text-xs text-stone sm:flex-row sm:items-center sm:justify-between"
            >
                <span>{{ copyright }}</span>
                <div
                    v-if="bottomLinks.length"
                    class="flex flex-wrap gap-x-4 gap-y-1"
                >
                    <NavLink
                        v-for="link in bottomLinks"
                        :key="link.id ?? link.label"
                        :item="link"
                        class="hover:text-white"
                    />
                </div>
            </div>
        </div>
    </footer>
</template>
