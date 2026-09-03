<script setup lang="ts">
import { Head, Link, router, setLayoutProps } from '@inertiajs/vue3';
import { ArrowLeft, Download, Search } from '@lucide/vue';
import { ref } from 'vue';
import ConfirmDelete from '@/components/admin/ConfirmDelete.vue';
import EmptyState from '@/components/admin/EmptyState.vue';
import Pagination from '@/components/admin/Pagination.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { formatDate, formatDateTime, money } from '@/lib/format';
import { edit, index } from '@/routes/admin/camps';
import {
    destroy,
    exportMethod,
    index as registrationsIndex,
} from '@/routes/admin/camps/registrations';
import { show as orderShow } from '@/routes/admin/orders';
import type { Paginated } from '@/types';
import type { AdminCampRegistration } from '@/types/camps';

const props = defineProps<{
    camp: {
        id: number;
        name: string;
        slug: string;
        starts_at: string;
        capacity: number | null;
        price: number;
        spots_remaining: number | null;
        paid_count: number;
    };
    registrations: Paginated<AdminCampRegistration>;
    filters: { q: string; status: string };
    statuses: { value: string; label: string }[];
}>();

setLayoutProps({
    breadcrumbs: [
        { title: 'Camps', href: index() },
        { title: props.camp.name, href: edit(props.camp.slug) },
        { title: 'Registrations', href: registrationsIndex(props.camp.slug) },
    ],
});

const q = ref(props.filters.q);
const status = ref(props.filters.status || 'all');

function applyFilters() {
    router.get(
        registrationsIndex(props.camp.slug).url,
        {
            q: q.value || undefined,
            status: status.value === 'all' ? undefined : status.value,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

const exportUrl = () =>
    exportMethod(props.camp.slug, {
        query: {
            q: q.value || undefined,
            status: status.value === 'all' ? undefined : status.value,
        },
    }).url;
</script>

<template>
    <Head :title="`Registrations — ${camp.name}`" />

    <div class="p-4 md:p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <Button as-child variant="ghost" size="sm" class="mb-2 -ml-2">
                    <Link :href="edit(camp.slug)"
                        ><ArrowLeft class="size-4" /> Back to camp</Link
                    >
                </Button>
                <Heading
                    :title="`${camp.name} — Registrations`"
                    :description="`${formatDateTime(camp.starts_at)} · ${camp.paid_count} paid${camp.capacity ? ` of ${camp.capacity}` : ''}${camp.spots_remaining !== null ? ` · ${camp.spots_remaining} spots left` : ''} · ${camp.price === 0 ? 'Free' : money(camp.price)}`"
                />
            </div>
            <Button as-child variant="outline">
                <a :href="exportUrl()"
                    ><Download class="size-4" /> Export CSV</a
                >
            </Button>
        </div>

        <form
            class="mb-4 flex flex-wrap items-end gap-3"
            @submit.prevent="applyFilters"
        >
            <div class="relative w-full max-w-xs">
                <Search
                    class="pointer-events-none absolute top-1/2 left-2.5 size-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    v-model="q"
                    type="search"
                    placeholder="Search player, parent, email…"
                    class="pl-8"
                />
            </div>
            <Select v-model="status" @update:model-value="applyFilters">
                <SelectTrigger class="w-44"
                    ><SelectValue placeholder="All statuses"
                /></SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">All statuses</SelectItem>
                    <SelectItem
                        v-for="s in statuses"
                        :key="s.value"
                        :value="s.value"
                        >{{ s.label }}</SelectItem
                    >
                </SelectContent>
            </Select>
            <Button type="submit" variant="secondary">Filter</Button>
        </form>

        <EmptyState
            v-if="registrations.data.length === 0"
            title="No registrations"
            :description="
                filters.q || filters.status
                    ? 'No registrations match your filters.'
                    : 'Nobody has registered for this camp yet.'
            "
        />

        <div v-else class="space-y-4">
            <div class="overflow-x-auto rounded-lg border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Player</TableHead>
                            <TableHead>Parent / Guardian</TableHead>
                            <TableHead>Emergency contact</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead>Registered</TableHead>
                            <TableHead class="w-[1%]"
                                ><span class="sr-only">Actions</span></TableHead
                            >
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow
                            v-for="registration in registrations.data"
                            :key="registration.id"
                        >
                            <TableCell>
                                <p class="font-medium">
                                    {{ registration.player_name }}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    Born
                                    {{
                                        formatDate(
                                            registration.player_birthdate,
                                        )
                                    }}
                                </p>
                                <p
                                    v-if="registration.medical_notes"
                                    class="mt-1 max-w-xs text-xs text-muted-foreground"
                                >
                                    <span class="font-medium">Medical:</span>
                                    {{ registration.medical_notes }}
                                </p>
                            </TableCell>
                            <TableCell>
                                <p>{{ registration.parent_name }}</p>
                                <a
                                    :href="`mailto:${registration.email}`"
                                    class="block text-xs text-muted-foreground hover:underline"
                                    >{{ registration.email }}</a
                                >
                                <a
                                    :href="`tel:${registration.phone}`"
                                    class="block text-xs text-muted-foreground hover:underline"
                                    >{{ registration.phone }}</a
                                >
                            </TableCell>
                            <TableCell class="text-sm">
                                <template
                                    v-if="registration.emergency_contact_name"
                                >
                                    <p>
                                        {{
                                            registration.emergency_contact_name
                                        }}
                                    </p>
                                    <p class="text-xs text-muted-foreground">
                                        {{
                                            registration.emergency_contact_phone
                                        }}
                                    </p>
                                </template>
                                <span v-else class="text-muted-foreground"
                                    >—</span
                                >
                            </TableCell>
                            <TableCell>
                                <StatusBadge
                                    :status="registration.status"
                                    :label="registration.status_label"
                                />
                                <Link
                                    v-if="
                                        registration.order_id &&
                                        registration.order_number
                                    "
                                    :href="orderShow(registration.order_id)"
                                    class="mt-1 block text-xs text-muted-foreground hover:underline"
                                >
                                    {{ registration.order_number }}
                                </Link>
                            </TableCell>
                            <TableCell class="text-sm whitespace-nowrap">{{
                                formatDateTime(registration.registered_at)
                            }}</TableCell>
                            <TableCell>
                                <ConfirmDelete
                                    :href="
                                        destroy([camp.slug, registration.id])
                                    "
                                    size="icon-sm"
                                    title="Delete this registration?"
                                    description="This removes the registration record. It does not refund any payment — do that in Stripe."
                                />
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
            <Pagination :paginator="registrations" />
        </div>
    </div>
</template>
