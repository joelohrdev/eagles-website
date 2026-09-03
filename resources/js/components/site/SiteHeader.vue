<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { Menu, ShoppingBag, X } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import NavLink from '@/components/site/NavLink.vue';
import { Button } from '@/components/ui/button';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { home } from '@/routes';
import { index as cartIndex } from '@/routes/cart';

const page = usePage();
const { isCurrentUrl } = useCurrentUrl();
const mobileOpen = ref(false);

const navItems = computed(() => page.props.navigation?.menus.header ?? []);
const settings = computed(() => page.props.navigation?.settings);
const cartCount = computed(() => page.props.cartCount ?? 0);
const orgName = computed(() => page.props.site?.org_name ?? page.props.name);

const showCta = computed(
    () =>
        !!settings.value?.nav_show_cta &&
        !!settings.value?.nav_cta_label &&
        !!settings.value?.nav_cta_url,
);
const showCart = computed(() => settings.value?.nav_show_cart ?? true);

/**
 * The menu is pushed right against the cart and CTA; with neither of them there
 * is nothing to sit at the right edge on desktop, so the actions column
 * collapses (its flex gap included) and the menu runs flush to the container.
 */
const hasActions = computed(() => showCart.value || showCta.value);

watch(
    () => page.url,
    () => {
        mobileOpen.value = false;
    },
);
</script>

<template>
    <header
        class="sticky top-0 z-40 border-b border-navy-light bg-navy text-snow"
    >
        <div
            class="container-site flex h-16 items-center justify-between gap-4"
        >
            <Link
                :href="home()"
                class="flex items-center gap-2"
                aria-label="Home"
            >
                <AppLogoIcon class="h-10 w-auto" />
                <span
                    class="font-display text-xl font-bold tracking-wide uppercase"
                >
                    {{ orgName }}
                </span>
            </Link>

            <nav
                class="ml-auto hidden items-center gap-1 lg:flex"
                aria-label="Main"
            >
                <NavLink
                    v-for="item in navItems"
                    :key="item.id ?? item.label"
                    :item="item"
                    class="rounded-md px-3 py-2 text-sm font-medium transition-colors hover:bg-navy-light hover:text-white"
                    :class="{
                        'bg-navy-light text-white':
                            !item.external && isCurrentUrl(item.href),
                    }"
                />
            </nav>

            <div
                class="flex items-center gap-2"
                :class="{ 'lg:hidden': !hasActions }"
            >
                <Button
                    v-if="showCart"
                    as-child
                    variant="ghost"
                    size="icon"
                    class="relative text-snow hover:bg-navy-light hover:text-white"
                >
                    <Link :href="cartIndex()" aria-label="Cart">
                        <ShoppingBag class="size-5" />
                        <span
                            v-if="cartCount > 0"
                            class="absolute -top-0.5 -right-0.5 flex size-4 items-center justify-center rounded-full bg-sky text-[10px] font-bold text-navy"
                        >
                            {{ cartCount }}
                        </span>
                    </Link>
                </Button>
                <Button
                    v-if="showCta"
                    as-child
                    class="hidden bg-sky font-semibold text-navy hover:bg-sky-dark hover:text-white sm:inline-flex"
                >
                    <NavLink
                        :item="{
                            id: null,
                            label: settings!.nav_cta_label!,
                            href: settings!.nav_cta_url!,
                            external: !settings!.nav_cta_url!.startsWith('/'),
                            new_tab: false,
                        }"
                    />
                </Button>
                <Button
                    variant="ghost"
                    size="icon"
                    class="text-snow hover:bg-navy-light hover:text-white lg:hidden"
                    :aria-expanded="mobileOpen"
                    aria-controls="mobile-nav"
                    aria-label="Toggle menu"
                    @click="mobileOpen = !mobileOpen"
                >
                    <X v-if="mobileOpen" class="size-5" />
                    <Menu v-else class="size-5" />
                </Button>
            </div>
        </div>

        <nav
            v-show="mobileOpen"
            id="mobile-nav"
            class="border-t border-navy-light bg-navy lg:hidden"
            aria-label="Mobile"
        >
            <div class="container-site flex flex-col py-2">
                <NavLink
                    v-for="item in navItems"
                    :key="item.id ?? item.label"
                    :item="item"
                    class="rounded-md px-3 py-3 text-base font-medium hover:bg-navy-light"
                    :class="{
                        'bg-navy-light':
                            !item.external && isCurrentUrl(item.href),
                    }"
                />
                <NavLink
                    v-if="showCta"
                    :item="{
                        id: null,
                        label: settings!.nav_cta_label!,
                        href: settings!.nav_cta_url!,
                        external: !settings!.nav_cta_url!.startsWith('/'),
                        new_tab: false,
                    }"
                    class="mt-2 rounded-md bg-sky px-3 py-3 text-center text-base font-semibold text-navy sm:hidden"
                />
            </div>
        </nav>
    </header>
</template>
