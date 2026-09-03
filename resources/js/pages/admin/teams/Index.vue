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
import { create, destroy, edit, index, reorder } from '@/routes/admin/teams';
import type { Paginated } from '@/types';
import type { Team } from '@/types/teams';

const props = defineProps<{
    teams: Paginated<Team>;
}>();

const {
    container,
    items: orderedTeams,
    move,
} = useSortableList<Team>(() => props.teams.data, reorder.url());

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Teams', href: index() }],
    },
});
</script>

<template>
    <Head title="Teams" />

    <div class="flex flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <Heading
                title="Teams"
                description="Manage the teams shown on the public Teams page. Drag a row by its handle to reorder them."
            />
            <Button as-child>
                <Link :href="create()"><Plus class="size-4" /> New team</Link>
            </Button>
        </div>

        <EmptyState
            v-if="teams.data.length === 0"
            title="No teams yet"
            description="Create your first team to show it on the website."
        >
            <Button as-child><Link :href="create()">New team</Link></Button>
        </EmptyState>

        <div v-else class="rounded-lg border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead class="w-10"></TableHead>
                        <TableHead class="w-16"></TableHead>
                        <TableHead>Team</TableHead>
                        <TableHead>Division</TableHead>
                        <TableHead class="hidden md:table-cell"
                            >Season</TableHead
                        >
                        <TableHead class="hidden md:table-cell"
                            >Coach</TableHead
                        >
                        <TableHead>Status</TableHead>
                        <TableHead class="text-right">Actions</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody ref="container">
                    <TableRow
                        v-for="(team, position) in orderedTeams"
                        :key="team.id"
                    >
                        <TableCell>
                            <DragHandle
                                :label="team.name"
                                @move="move(position, $event)"
                            />
                        </TableCell>
                        <TableCell>
                            <img
                                v-if="team.photo_thumbnail_url"
                                :src="team.photo_thumbnail_url"
                                :alt="team.name"
                                class="size-10 rounded object-cover"
                            />
                            <div v-else class="size-10 rounded bg-muted" />
                        </TableCell>
                        <TableCell class="font-medium">
                            <Link :href="edit(team)" class="hover:underline">{{
                                team.name
                            }}</Link>
                        </TableCell>
                        <TableCell>{{ team.division }}</TableCell>
                        <TableCell class="hidden md:table-cell">{{
                            team.season ?? '—'
                        }}</TableCell>
                        <TableCell class="hidden md:table-cell">{{
                            team.coach?.name ?? '—'
                        }}</TableCell>
                        <TableCell>
                            <StatusBadge
                                :status="team.is_active ? 'active' : 'inactive'"
                            />
                        </TableCell>
                        <TableCell class="text-right">
                            <div class="flex justify-end gap-1">
                                <Button as-child variant="ghost" size="icon-sm">
                                    <Link :href="edit(team)" aria-label="Edit"
                                        ><Pencil class="size-4"
                                    /></Link>
                                </Button>
                                <ConfirmDelete
                                    :href="destroy(team)"
                                    size="icon-sm"
                                    title="Delete this team?"
                                    :description="`${team.name} will be removed from the website.`"
                                />
                            </div>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <Pagination :paginator="teams" />
    </div>
</template>
