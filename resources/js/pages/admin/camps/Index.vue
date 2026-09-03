<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Pencil, Plus, Users } from '@lucide/vue';
import ConfirmDelete from '@/components/admin/ConfirmDelete.vue';
import EmptyState from '@/components/admin/EmptyState.vue';
import Pagination from '@/components/admin/Pagination.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { formatDateTime, money } from '@/lib/format';
import { create, destroy, edit, index } from '@/routes/admin/camps';
import { index as registrationsIndex } from '@/routes/admin/camps/registrations';
import type { Paginated } from '@/types';
import type { AdminCampRow } from '@/types/camps';

defineProps<{
    camps: Paginated<AdminCampRow>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Camps', href: index() }],
    },
});
</script>

<template>
    <Head title="Camps" />

    <div class="p-4 md:p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <Heading
                title="Camps & Clinics"
                description="Manage camps, pricing, registration windows, and registrations."
            />
            <Button as-child>
                <Link :href="create()"><Plus class="size-4" /> New camp</Link>
            </Button>
        </div>

        <EmptyState
            v-if="camps.data.length === 0"
            title="No camps yet"
            description="Create your first camp or clinic. You can save it as a draft and publish when ready."
        >
            <Button as-child>
                <Link :href="create()"><Plus class="size-4" /> New camp</Link>
            </Button>
        </EmptyState>

        <div v-else class="space-y-4">
            <div class="overflow-x-auto rounded-lg border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Camp</TableHead>
                            <TableHead>Date</TableHead>
                            <TableHead>Price</TableHead>
                            <TableHead>Registration</TableHead>
                            <TableHead class="text-right">Registered</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead class="w-[1%]"
                                ><span class="sr-only">Actions</span></TableHead
                            >
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="camp in camps.data" :key="camp.id">
                            <TableCell>
                                <div class="flex items-center gap-3">
                                    <img
                                        v-if="camp.image_thumbnail_url"
                                        :src="camp.image_thumbnail_url"
                                        alt=""
                                        class="size-10 rounded object-cover"
                                    />
                                    <div
                                        v-else
                                        class="size-10 rounded bg-muted"
                                    />
                                    <div>
                                        <Link
                                            :href="edit(camp.slug)"
                                            class="font-medium hover:underline"
                                            >{{ camp.name }}</Link
                                        >
                                        <p
                                            v-if="camp.location"
                                            class="text-xs text-muted-foreground"
                                        >
                                            {{ camp.location }}
                                        </p>
                                    </div>
                                </div>
                            </TableCell>
                            <TableCell class="text-sm whitespace-nowrap">{{
                                formatDateTime(camp.starts_at)
                            }}</TableCell>
                            <TableCell>{{
                                camp.price === 0 ? 'Free' : money(camp.price)
                            }}</TableCell>
                            <TableCell>
                                <StatusBadge
                                    :status="camp.registration_state"
                                />
                            </TableCell>
                            <TableCell class="text-right tabular-nums">
                                <Link
                                    :href="registrationsIndex(camp.slug)"
                                    class="hover:underline"
                                >
                                    {{ camp.paid_registrations_count
                                    }}<span
                                        v-if="camp.capacity"
                                        class="text-muted-foreground"
                                    >
                                        / {{ camp.capacity }}</span
                                    >
                                </Link>
                                <p
                                    v-if="
                                        camp.active_registrations_count >
                                        camp.paid_registrations_count
                                    "
                                    class="text-xs text-muted-foreground"
                                >
                                    +{{
                                        camp.active_registrations_count -
                                        camp.paid_registrations_count
                                    }}
                                    pending
                                </p>
                            </TableCell>
                            <TableCell>
                                <StatusBadge
                                    :status="
                                        camp.is_published
                                            ? 'published'
                                            : 'draft'
                                    "
                                />
                            </TableCell>
                            <TableCell>
                                <div
                                    class="flex items-center justify-end gap-1"
                                >
                                    <Button
                                        as-child
                                        variant="ghost"
                                        size="icon-sm"
                                    >
                                        <Link
                                            :href="
                                                registrationsIndex(camp.slug)
                                            "
                                            aria-label="Registrations"
                                            ><Users class="size-4"
                                        /></Link>
                                    </Button>
                                    <Button
                                        as-child
                                        variant="ghost"
                                        size="icon-sm"
                                    >
                                        <Link
                                            :href="edit(camp.slug)"
                                            aria-label="Edit"
                                            ><Pencil class="size-4"
                                        /></Link>
                                    </Button>
                                    <ConfirmDelete
                                        :href="destroy(camp.slug)"
                                        size="icon-sm"
                                        title="Delete this camp?"
                                        description="All registrations for this camp will also be deleted."
                                    />
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
            <Pagination :paginator="camps" />
        </div>
    </div>
</template>
