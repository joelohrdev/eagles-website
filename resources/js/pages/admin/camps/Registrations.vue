<script setup lang="ts">
import { Head, Link, router, setLayoutProps } from '@inertiajs/vue3';
import {
    ArrowLeft,
    ChevronDown,
    Download,
    History,
    Search,
    TicketPercent,
} from '@lucide/vue';
import { ref, watch } from 'vue';
import DropDaysDialog from '@/components/admin/camps/DropDaysDialog.vue';
import ConfirmDelete from '@/components/admin/ConfirmDelete.vue';
import EmptyState from '@/components/admin/EmptyState.vue';
import Pagination from '@/components/admin/Pagination.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
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
    roster,
} from '@/routes/admin/camps/registrations';
import { show as orderShow } from '@/routes/admin/orders';
import type { Paginated } from '@/types';
import type { AdminCampRegistration } from '@/types/camps';

const props = defineProps<{
    camp: {
        id: number;
        name: string;
        slug: string;
        starts_at: string | null;
        capacity: number | null;
        price: number;
        spots_remaining: number | null;
        paid_count: number;
    };
    registrations: Paginated<AdminCampRegistration>;
    filters: {
        q: string;
        status: string;
        day: number | null;
        session: number | null;
        age_group: number | null;
    };
    statuses: { value: string; label: string }[];
    days: { id: number; label: string }[];
    sessions: { id: number; name: string }[];
    /** The activity log, for admins only; each row links to its history. */
    historyUrl: string | null;
    canRefund: boolean;
    ageGroups: { id: number; name: string }[];
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
const day = ref(props.filters.day?.toString() ?? 'all');
const session = ref(props.filters.session?.toString() ?? 'all');
const ageGroup = ref(props.filters.age_group?.toString() ?? 'all');

const filterQuery = () => ({
    q: q.value || undefined,
    status: status.value === 'all' ? undefined : status.value,
    day: day.value === 'all' ? undefined : day.value,
    session: session.value === 'all' ? undefined : session.value,
    age_group: ageGroup.value === 'all' ? undefined : ageGroup.value,
});

/** Search applies as you type (after a short pause) or on Enter; the dropdowns apply on change. */
let searchTimer: ReturnType<typeof setTimeout> | undefined;

watch(q, () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(applyFilters, 350);
});

function applyFilters() {
    clearTimeout(searchTimer);
    router.get(registrationsIndex(props.camp.slug).url, filterQuery(), {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

const exportUrl = () =>
    exportMethod(props.camp.slug, { query: filterQuery() }).url;

/** One row per player per booked day, for the camp lead's daily sign-in sheets. */
const rosterUrl = () => roster(props.camp.slug, { query: filterQuery() }).url;
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
                    :description="`${camp.starts_at ? formatDateTime(camp.starts_at) : 'Dates TBD'} · ${camp.paid_count} paid${camp.spots_remaining !== null ? ` · ${camp.spots_remaining} spots left` : ''}`"
                />
            </div>
            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <Button variant="outline"
                        ><Download class="size-4" /> Export CSV
                        <ChevronDown class="size-4"
                    /></Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" class="w-72">
                    <DropdownMenuItem as-child>
                        <a
                            :href="rosterUrl()"
                            class="flex flex-col items-start"
                        >
                            <span class="font-medium">Daily roster</span>
                            <span class="text-xs text-muted-foreground"
                                >One row per player per day, sorted by day,
                                session and age group. Paid players unless you
                                pick a status.</span
                            >
                        </a>
                    </DropdownMenuItem>
                    <DropdownMenuItem as-child>
                        <a
                            :href="exportUrl()"
                            class="flex flex-col items-start"
                        >
                            <span class="font-medium">Registrations</span>
                            <span class="text-xs text-muted-foreground"
                                >One row per registration, with payment and
                                coupon details.</span
                            >
                        </a>
                    </DropdownMenuItem>
                    <p class="px-2 pt-1 pb-2 text-xs text-muted-foreground">
                        Both use the filters on this page.
                    </p>
                </DropdownMenuContent>
            </DropdownMenu>
        </div>

        <form
            class="mb-4 grid gap-3 sm:grid-cols-2 lg:flex lg:items-center"
            @submit.prevent="applyFilters"
        >
            <div class="relative sm:col-span-2 lg:min-w-48 lg:flex-1">
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
                <SelectTrigger class="w-full lg:w-40"
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
            <Select
                v-if="sessions.length > 1"
                v-model="session"
                @update:model-value="applyFilters"
            >
                <SelectTrigger class="w-full lg:w-44"
                    ><SelectValue placeholder="All sessions"
                /></SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">All sessions</SelectItem>
                    <SelectItem
                        v-for="s in sessions"
                        :key="s.id"
                        :value="s.id.toString()"
                        >{{ s.name }}</SelectItem
                    >
                </SelectContent>
            </Select>
            <Select
                v-if="days.length > 1"
                v-model="day"
                @update:model-value="applyFilters"
            >
                <SelectTrigger class="w-full lg:w-52"
                    ><SelectValue placeholder="All days"
                /></SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">All days</SelectItem>
                    <SelectItem
                        v-for="d in days"
                        :key="d.id"
                        :value="d.id.toString()"
                        >{{ d.label }}</SelectItem
                    >
                </SelectContent>
            </Select>
            <Select
                v-if="ageGroups.length > 0"
                v-model="ageGroup"
                @update:model-value="applyFilters"
            >
                <SelectTrigger class="w-full lg:w-40"
                    ><SelectValue placeholder="All age groups"
                /></SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">All age groups</SelectItem>
                    <SelectItem
                        v-for="g in ageGroups"
                        :key="g.id"
                        :value="g.id.toString()"
                        >{{ g.name }}</SelectItem
                    >
                </SelectContent>
            </Select>
        </form>

        <EmptyState
            v-if="registrations.data.length === 0"
            title="No registrations"
            :description="
                filters.q ||
                filters.status ||
                filters.day ||
                filters.session ||
                filters.age_group
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
                            <TableHead>Sessions</TableHead>
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
                            <TableCell class="text-sm">
                                <p
                                    v-if="registration.age_group"
                                    class="font-medium"
                                >
                                    {{ registration.age_group }}
                                </p>
                                <p
                                    v-for="line in registration.schedule"
                                    :key="line"
                                    class="text-xs whitespace-nowrap text-muted-foreground"
                                >
                                    {{ line }}
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
                                <span
                                    v-if="registration.discount_label"
                                    class="mt-1 inline-flex items-center gap-1 rounded-full bg-accent px-2 py-0.5 text-xs font-medium text-sky-dark"
                                    :title="`${registration.discount_label} took ${money(registration.discount)} off this order`"
                                >
                                    <TicketPercent class="size-3.5" />
                                    {{ registration.discount_label }}
                                    · −{{ money(registration.discount) }}
                                </span>
                            </TableCell>
                            <TableCell class="text-sm whitespace-nowrap">{{
                                formatDateTime(registration.registered_at)
                            }}</TableCell>
                            <TableCell
                                class="flex items-center justify-end gap-1"
                            >
                                <Button
                                    v-if="historyUrl"
                                    as-child
                                    variant="ghost"
                                    size="icon-sm"
                                >
                                    <Link
                                        :href="`${historyUrl}?registration=${registration.id}`"
                                        :aria-label="`History for ${registration.player_name}`"
                                        title="History"
                                        ><History class="size-4"
                                    /></Link>
                                </Button>
                                <DropDaysDialog
                                    v-if="
                                        canRefund &&
                                        registration.status === 'paid'
                                    "
                                    :camp-slug="camp.slug"
                                    :registration-id="registration.id"
                                    :player-name="registration.player_name"
                                    :amount-kept="registration.amount_kept"
                                    :days="
                                        days.filter((day) =>
                                            registration.day_ids.includes(
                                                day.id,
                                            ),
                                        )
                                    "
                                />
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
