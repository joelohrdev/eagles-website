<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Search } from '@lucide/vue';
import EmptyState from '@/components/admin/EmptyState.vue';
import Pagination from '@/components/admin/Pagination.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { formatDateTime, money } from '@/lib/format';
import { index, show } from '@/routes/admin/orders';
import type { Paginated } from '@/types';
import type { Option, Order } from '@/types/merch';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Orders', href: index() }],
    },
});

defineProps<{
    orders: Paginated<Order>;
    filters: { status: string | null; type: string | null; q: string | null };
    statuses: Option[];
    types: Option[];
}>();
</script>

<template>
    <Head title="Orders" />

    <div class="flex flex-col gap-6 p-4 md:p-6">
        <Heading
            title="Orders"
            description="Merch purchases and paid camp registrations. Payments are processed by Stripe."
        />

        <form
            :action="index.url()"
            method="get"
            class="flex flex-wrap items-end gap-3 rounded-lg border bg-muted/30 p-4"
        >
            <div class="grid gap-1">
                <label for="q" class="text-xs font-medium text-muted-foreground"
                    >Search</label
                >
                <div class="relative">
                    <Search
                        class="pointer-events-none absolute top-2.5 left-2.5 size-4 text-muted-foreground"
                    />
                    <Input
                        id="q"
                        name="q"
                        :default-value="filters.q ?? ''"
                        placeholder="Order #, name, or email"
                        class="w-64 pl-8"
                    />
                </div>
            </div>
            <div class="grid gap-1">
                <label
                    for="status"
                    class="text-xs font-medium text-muted-foreground"
                    >Status</label
                >
                <select
                    id="status"
                    name="status"
                    class="h-9 rounded-md border bg-background px-3 text-sm"
                    :value="filters.status ?? ''"
                >
                    <option value="">All statuses</option>
                    <option
                        v-for="s in statuses"
                        :key="s.value"
                        :value="s.value"
                    >
                        {{ s.label }}
                    </option>
                </select>
            </div>
            <div class="grid gap-1">
                <label
                    for="type"
                    class="text-xs font-medium text-muted-foreground"
                    >Type</label
                >
                <select
                    id="type"
                    name="type"
                    class="h-9 rounded-md border bg-background px-3 text-sm"
                    :value="filters.type ?? ''"
                >
                    <option value="">All types</option>
                    <option v-for="t in types" :key="t.value" :value="t.value">
                        {{ t.label }}
                    </option>
                </select>
            </div>
            <Button type="submit" variant="secondary">Filter</Button>
            <Button
                as-child
                variant="ghost"
                v-if="filters.q || filters.status || filters.type"
            >
                <Link :href="index()">Clear</Link>
            </Button>
        </form>

        <EmptyState
            v-if="orders.data.length === 0"
            title="No orders found"
            description="Orders appear here as soon as a customer completes payment on Stripe."
        />

        <div v-else class="rounded-lg border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Order</TableHead>
                        <TableHead>Date</TableHead>
                        <TableHead>Customer</TableHead>
                        <TableHead>Type</TableHead>
                        <TableHead>Items</TableHead>
                        <TableHead class="text-right">Total</TableHead>
                        <TableHead>Status</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="order in orders.data" :key="order.id">
                        <TableCell>
                            <Link
                                :href="show(order.id)"
                                class="font-mono text-sm font-medium hover:underline"
                                >{{ order.number }}</Link
                            >
                        </TableCell>
                        <TableCell class="text-sm whitespace-nowrap">{{
                            formatDateTime(order.created_at)
                        }}</TableCell>
                        <TableCell>
                            <div class="font-medium">{{ order.name }}</div>
                            <div class="text-xs text-muted-foreground">
                                {{ order.email }}
                            </div>
                        </TableCell>
                        <TableCell class="capitalize">{{
                            order.type === 'camp'
                                ? 'Camp registration'
                                : 'Merch'
                        }}</TableCell>
                        <TableCell>{{ order.items_count ?? 0 }}</TableCell>
                        <TableCell class="text-right font-medium">{{
                            money(order.total)
                        }}</TableCell>
                        <TableCell
                            ><StatusBadge :status="order.status"
                        /></TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <Pagination :paginator="orders" />
    </div>
</template>
