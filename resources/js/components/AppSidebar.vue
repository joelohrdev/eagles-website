<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    Building2,
    CalendarDays,
    ClipboardList,
    ExternalLink,
    Globe,
    Inbox,
    ListTree,
    LayoutGrid,
    Package,
    Receipt,
    Search,
    Settings2,
    Shield,
    UserCog,
    Users,
} from '@lucide/vue';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { home } from '@/routes';
import { dashboard } from '@/routes/admin';
import { index as campsIndex } from '@/routes/admin/camps';
import { index as coachesIndex } from '@/routes/admin/coaches';
import { index as contactIndex } from '@/routes/admin/contact-submissions';
import { index as facilityIndex } from '@/routes/admin/facility-photos';
import { index as navigationIndex } from '@/routes/admin/navigation';
import { index as ordersIndex } from '@/routes/admin/orders';
import { index as productsIndex } from '@/routes/admin/products';
import { index as seoIndex } from '@/routes/admin/seo';
import { edit as settingsEdit } from '@/routes/admin/settings';
import { index as teamsIndex } from '@/routes/admin/teams';
import { index as tryoutsIndex } from '@/routes/admin/tryouts';
import { index as usersIndex } from '@/routes/admin/users';
import type { NavItem } from '@/types';

const page = usePage();
const isAdmin = computed(() => page.props.auth.isAdmin);

const contentNavItems: NavItem[] = [
    { title: 'Dashboard', href: dashboard(), icon: LayoutGrid, exact: true },
    { title: 'Teams', href: teamsIndex(), icon: Shield },
    { title: 'Coaches', href: coachesIndex(), icon: Users },
    { title: 'Camps', href: campsIndex(), icon: CalendarDays },
    { title: 'Tryouts', href: tryoutsIndex(), icon: ClipboardList },
    { title: 'Facility Photos', href: facilityIndex(), icon: Building2 },
    { title: 'Products', href: productsIndex(), icon: Package },
    { title: 'Orders', href: ordersIndex(), icon: Receipt },
    { title: 'Contact Inbox', href: contactIndex(), icon: Inbox },
    { title: 'SEO & Sharing', href: seoIndex(), icon: Search },
];

const adminNavItems = computed<NavItem[]>(() =>
    isAdmin.value
        ? [
              {
                  title: 'Site Settings',
                  href: settingsEdit('organization'),
                  icon: Settings2,
              },
              {
                  title: 'Navigation & Footer',
                  href: navigationIndex(),
                  icon: ListTree,
              },
              { title: 'Users & Invites', href: usersIndex(), icon: UserCog },
          ]
        : [],
);

const footerNavItems: NavItem[] = [
    { title: 'View site', href: home(), icon: Globe },
    {
        title: 'Stripe dashboard',
        href: 'https://dashboard.stripe.com/',
        icon: ExternalLink,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="contentNavItems" label="Content" />
            <NavMain
                v-if="adminNavItems.length"
                :items="adminNavItems"
                label="Administration"
            />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
