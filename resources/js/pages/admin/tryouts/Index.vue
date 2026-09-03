<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Pencil, Plus, Users } from '@lucide/vue';
import ConfirmDelete from '@/components/admin/ConfirmDelete.vue';
import EmptyState from '@/components/admin/EmptyState.vue';
import Pagination from '@/components/admin/Pagination.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { formatDateTime } from '@/lib/format';
import { create, destroy, edit, index } from '@/routes/admin/tryouts';
import { index as registrationsIndex } from '@/routes/admin/tryouts/registrations';
import type { Paginated } from '@/types';
import type { Tryout } from '@/types/tryouts';

defineProps<{
    tryouts: Paginated<Tryout>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Tryouts', href: index() }],
    },
});
</script>

<template>
    <Head title="Tryouts" />

    <div class="p-6">
        <div class="mb-6 flex items-start justify-between gap-4">
            <Heading
                title="Tryouts"
                description="Schedule tryouts, control registration windows, and view sign-ups."
                class="mb-0"
            />
            <Button as-child>
                <Link :href="create()"><Plus class="size-4" /> New tryout</Link>
            </Button>
        </div>

        <EmptyState
            v-if="tryouts.data.length === 0"
            title="No tryouts yet"
            description="Create your first tryout to start taking registrations."
        >
            <Button as-child>
                <Link :href="create()"><Plus class="size-4" /> New tryout</Link>
            </Button>
        </EmptyState>

        <div v-else class="space-y-4">
            <div class="overflow-x-auto rounded-lg border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Tryout</TableHead>
                            <TableHead>Date</TableHead>
                            <TableHead>Registration</TableHead>
                            <TableHead class="text-right">Sign-ups</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead class="w-32 text-right"
                                >Actions</TableHead
                            >
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow
                            v-for="tryout in tryouts.data"
                            :key="tryout.id"
                        >
                            <TableCell>
                                <Link
                                    :href="edit(tryout.slug)"
                                    class="font-medium hover:underline"
                                    >{{ tryout.title }}</Link
                                >
                                <p class="text-xs text-muted-foreground">
                                    {{ tryout.division
                                    }}<span v-if="tryout.location">
                                        · {{ tryout.location }}</span
                                    >
                                </p>
                            </TableCell>
                            <TableCell class="whitespace-nowrap">{{
                                formatDateTime(tryout.event_at)
                            }}</TableCell>
                            <TableCell>
                                <StatusBadge
                                    :status="tryout.registration_state"
                                />
                                <p
                                    v-if="tryout.capacity"
                                    class="mt-1 text-xs text-muted-foreground"
                                >
                                    {{ tryout.spots_remaining }} of
                                    {{ tryout.capacity }} left
                                </p>
                            </TableCell>
                            <TableCell class="text-right">
                                <Link
                                    :href="registrationsIndex(tryout.slug)"
                                    class="inline-flex items-center gap-1 hover:underline"
                                >
                                    <Users class="size-4" />
                                    {{ tryout.registrations_count ?? 0 }}
                                </Link>
                            </TableCell>
                            <TableCell>
                                <Badge
                                    :variant="
                                        tryout.is_published
                                            ? 'default'
                                            : 'outline'
                                    "
                                    >{{
                                        tryout.is_published
                                            ? 'Published'
                                            : 'Draft'
                                    }}</Badge
                                >
                            </TableCell>
                            <TableCell class="text-right">
                                <div
                                    class="flex items-center justify-end gap-1"
                                >
                                    <Button
                                        as-child
                                        variant="ghost"
                                        size="icon-sm"
                                    >
                                        <Link
                                            :href="edit(tryout.slug)"
                                            aria-label="Edit"
                                            ><Pencil class="size-4"
                                        /></Link>
                                    </Button>
                                    <ConfirmDelete
                                        :href="destroy(tryout.slug)"
                                        size="icon-sm"
                                        title="Delete this tryout?"
                                        description="All registrations for this tryout will be deleted too."
                                    />
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
            <Pagination :paginator="tryouts" />
        </div>
    </div>
</template>
