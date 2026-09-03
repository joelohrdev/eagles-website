<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Pencil, Plus } from '@lucide/vue';
import ConfirmDelete from '@/components/admin/ConfirmDelete.vue';
import DragHandle from '@/components/admin/DragHandle.vue';
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
import { useSortableList } from '@/composables/useSortableList';
import { create, destroy, edit, index, reorder } from '@/routes/admin/coaches';
import type { Paginated } from '@/types';
import type { Coach } from '@/types/teams';

const props = defineProps<{
    coaches: Paginated<Coach>;
}>();

const {
    container,
    items: orderedCoaches,
    move,
} = useSortableList<Coach>(() => props.coaches.data, reorder.url());

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Coaches', href: index() }],
    },
});
</script>

<template>
    <Head title="Coaches" />

    <div class="flex flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <Heading
                title="Coaches"
                description="Manage the coaching staff shown on the website. Drag a row by its handle to reorder them."
            />
            <Button as-child>
                <Link :href="create()"><Plus class="size-4" /> New coach</Link>
            </Button>
        </div>

        <EmptyState
            v-if="coaches.data.length === 0"
            title="No coaches yet"
            description="Add your coaching staff to show them on the website."
        >
            <Button as-child><Link :href="create()">New coach</Link></Button>
        </EmptyState>

        <div v-else class="rounded-lg border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead class="w-10"></TableHead>
                        <TableHead class="w-16"></TableHead>
                        <TableHead>Name</TableHead>
                        <TableHead class="hidden md:table-cell"
                            >Title</TableHead
                        >
                        <TableHead class="hidden md:table-cell"
                            >Teams</TableHead
                        >
                        <TableHead>Status</TableHead>
                        <TableHead class="text-right">Actions</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody ref="container">
                    <TableRow
                        v-for="(coach, position) in orderedCoaches"
                        :key="coach.id"
                    >
                        <TableCell>
                            <DragHandle
                                :label="coach.name"
                                @move="move(position, $event)"
                            />
                        </TableCell>
                        <TableCell>
                            <img
                                v-if="coach.photo_thumbnail_url"
                                :src="coach.photo_thumbnail_url"
                                :alt="coach.name"
                                class="size-10 rounded-full object-cover"
                            />
                            <div v-else class="size-10 rounded-full bg-muted" />
                        </TableCell>
                        <TableCell class="font-medium">
                            <Link :href="edit(coach)" class="hover:underline">{{
                                coach.name
                            }}</Link>
                        </TableCell>
                        <TableCell class="hidden md:table-cell">{{
                            coach.title ?? '—'
                        }}</TableCell>
                        <TableCell class="hidden md:table-cell">{{
                            coach.teams_count ?? 0
                        }}</TableCell>
                        <TableCell>
                            <StatusBadge
                                :status="
                                    coach.is_active ? 'active' : 'inactive'
                                "
                            />
                        </TableCell>
                        <TableCell class="text-right">
                            <div class="flex justify-end gap-1">
                                <Button as-child variant="ghost" size="icon-sm">
                                    <Link :href="edit(coach)" aria-label="Edit"
                                        ><Pencil class="size-4"
                                    /></Link>
                                </Button>
                                <ConfirmDelete
                                    :href="destroy(coach)"
                                    size="icon-sm"
                                    title="Delete this coach?"
                                    :description="`${coach.name} will be removed. Teams assigned to them will be left without a coach.`"
                                />
                            </div>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <Pagination :paginator="coaches" />
    </div>
</template>
