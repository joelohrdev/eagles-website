<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    CalendarDays,
    ClipboardList,
    DollarSign,
    Inbox,
    Receipt,
    UserPlus,
    Users,
} from '@lucide/vue';
import { computed } from 'vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import Heading from '@/components/Heading.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { formatDate, money } from '@/lib/format';
import { dashboard } from '@/routes/admin';
import { index as campsIndex } from '@/routes/admin/camps';
import {
    index as contactIndex,
    show as contactShow,
} from '@/routes/admin/contact-submissions';
import { index as ordersIndex, show as orderShow } from '@/routes/admin/orders';
import { index as tryoutsIndex } from '@/routes/admin/tryouts';
import { index as registrationsIndex } from '@/routes/admin/tryouts/registrations';
import { index as usersIndex } from '@/routes/admin/users';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Dashboard', href: dashboard() }],
    },
});

const props = defineProps<{
    stats: {
        open_tryouts: number;
        tryout_registrations_30d: number;
        upcoming_camps: number;
        camp_registrations_30d: number;
        unread_messages: number;
        orders_awaiting_fulfillment: number;
        revenue_30d: number;
        pending_invitations: number | null;
    };
    recentOrders: {
        id: number;
        number: string;
        type: string;
        name: string;
        email: string;
        total: number;
        status: string;
        created_at: string;
    }[];
    recentTryoutRegistrations: {
        id: number;
        tryout_id: number;
        player_first_name: string;
        player_last_name: string;
        parent_name: string;
        registered_at: string;
        tryout: { id: number; title: string; slug: string } | null;
    }[];
    recentMessages: {
        id: number;
        name: string;
        email: string;
        subject: string | null;
        read_at: string | null;
        created_at: string;
    }[];
}>();

const tiles = computed(() => {
    const items = [
        {
            label: 'Open tryouts',
            value: props.stats.open_tryouts,
            icon: ClipboardList,
            href: tryoutsIndex(),
        },
        {
            label: 'Tryout signups (30d)',
            value: props.stats.tryout_registrations_30d,
            icon: Users,
            href: tryoutsIndex(),
        },
        {
            label: 'Upcoming camps',
            value: props.stats.upcoming_camps,
            icon: CalendarDays,
            href: campsIndex(),
        },
        {
            label: 'Camp signups (30d)',
            value: props.stats.camp_registrations_30d,
            icon: Users,
            href: campsIndex(),
        },
        {
            label: 'Unread messages',
            value: props.stats.unread_messages,
            icon: Inbox,
            href: contactIndex({ query: { filter: 'unread' } }),
        },
        {
            label: 'Orders to fulfill',
            value: props.stats.orders_awaiting_fulfillment,
            icon: Receipt,
            href: ordersIndex({ query: { status: 'paid', type: 'merch' } }),
        },
        {
            label: 'Revenue (30d)',
            value: money(props.stats.revenue_30d),
            icon: DollarSign,
            href: ordersIndex(),
        },
    ];

    if (props.stats.pending_invitations !== null) {
        items.push({
            label: 'Pending invites',
            value: props.stats.pending_invitations,
            icon: UserPlus,
            href: usersIndex(),
        });
    }

    return items;
});
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex flex-col gap-6 p-4 md:p-6">
        <Heading
            title="Dashboard"
            description="What's happening across the site."
        />

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <Link
                v-for="tile in tiles"
                :key="tile.label"
                :href="tile.href"
                class="rounded-lg border bg-card p-4 shadow-sm transition-colors hover:bg-accent/50"
            >
                <div class="flex items-center justify-between">
                    <p class="text-sm text-muted-foreground">
                        {{ tile.label }}
                    </p>
                    <component :is="tile.icon" class="size-4 text-sky" />
                </div>
                <p class="mt-2 font-display text-3xl font-bold">
                    {{ tile.value }}
                </p>
            </Link>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <Card>
                <CardHeader class="flex flex-row items-center justify-between">
                    <CardTitle>Recent orders</CardTitle>
                    <Link
                        :href="ordersIndex()"
                        class="text-sm text-sky hover:underline"
                        >View all</Link
                    >
                </CardHeader>
                <CardContent>
                    <p
                        v-if="!recentOrders.length"
                        class="text-sm text-muted-foreground"
                    >
                        No orders yet.
                    </p>
                    <ul v-else class="divide-y">
                        <li
                            v-for="order in recentOrders"
                            :key="order.id"
                            class="py-2"
                        >
                            <Link
                                :href="orderShow(order.id)"
                                class="flex items-center justify-between gap-2 text-sm"
                            >
                                <span>
                                    <span class="font-medium">{{
                                        order.number
                                    }}</span>
                                    <span
                                        class="block text-xs text-muted-foreground"
                                        >{{ order.name }} ·
                                        {{ formatDate(order.created_at) }}</span
                                    >
                                </span>
                                <span class="flex items-center gap-2">
                                    <span>{{ money(order.total) }}</span>
                                    <StatusBadge :status="order.status" />
                                </span>
                            </Link>
                        </li>
                    </ul>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between">
                    <CardTitle>Recent tryout signups</CardTitle>
                    <Link
                        :href="tryoutsIndex()"
                        class="text-sm text-sky hover:underline"
                        >Tryouts</Link
                    >
                </CardHeader>
                <CardContent>
                    <p
                        v-if="!recentTryoutRegistrations.length"
                        class="text-sm text-muted-foreground"
                    >
                        No registrations yet.
                    </p>
                    <ul v-else class="divide-y">
                        <li
                            v-for="reg in recentTryoutRegistrations"
                            :key="reg.id"
                            class="py-2 text-sm"
                        >
                            <Link
                                :href="
                                    reg.tryout
                                        ? registrationsIndex(reg.tryout.slug)
                                        : tryoutsIndex()
                                "
                                class="block"
                            >
                                <span class="font-medium"
                                    >{{ reg.player_first_name }}
                                    {{ reg.player_last_name }}</span
                                >
                                <span
                                    class="block text-xs text-muted-foreground"
                                >
                                    {{ reg.tryout?.title ?? 'Tryout' }} ·
                                    {{ formatDate(reg.registered_at) }}
                                </span>
                            </Link>
                        </li>
                    </ul>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between">
                    <CardTitle>Recent messages</CardTitle>
                    <Link
                        :href="contactIndex()"
                        class="text-sm text-sky hover:underline"
                        >Inbox</Link
                    >
                </CardHeader>
                <CardContent>
                    <p
                        v-if="!recentMessages.length"
                        class="text-sm text-muted-foreground"
                    >
                        No messages yet.
                    </p>
                    <ul v-else class="divide-y">
                        <li
                            v-for="msg in recentMessages"
                            :key="msg.id"
                            class="py-2 text-sm"
                        >
                            <Link
                                :href="contactShow(msg.id)"
                                class="flex items-center justify-between gap-2"
                            >
                                <span>
                                    <span
                                        :class="
                                            msg.read_at
                                                ? 'font-normal'
                                                : 'font-semibold'
                                        "
                                        >{{ msg.name }}</span
                                    >
                                    <span
                                        class="block text-xs text-muted-foreground"
                                        >{{ msg.subject || 'No subject' }} ·
                                        {{ formatDate(msg.created_at) }}</span
                                    >
                                </span>
                                <span
                                    v-if="!msg.read_at"
                                    class="size-2 rounded-full bg-sky"
                                    aria-label="Unread"
                                />
                            </Link>
                        </li>
                    </ul>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
