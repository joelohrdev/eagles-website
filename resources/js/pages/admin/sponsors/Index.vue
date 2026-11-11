<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ExternalLink, Pencil, Plus } from '@lucide/vue';
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
import { create, destroy, edit, index, reorder } from '@/routes/admin/sponsors';
import type { Paginated } from '@/types';
import type { Sponsor } from '@/types/sponsors';

const props = defineProps<{
    sponsors: Paginated<Sponsor>;
}>();

const {
    container,
    items: orderedSponsors,
    move,
} = useSortableList<Sponsor>(() => props.sponsors.data, reorder.url());

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Sponsors', href: index() }],
    },
});
</script>

<template>
    <Head title="Sponsors" />

    <div class="flex flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <Heading
                title="Sponsors"
                description="Logos shown on the homepage. Up to four sit still; with five or more they scroll. Drag a row by its handle to reorder them."
            />
            <Button as-child>
                <Link :href="create()"
                    ><Plus class="size-4" /> New sponsor</Link
                >
            </Button>
        </div>

        <EmptyState
            v-if="sponsors.data.length === 0"
            title="No sponsors yet"
            description="Add a sponsor's logo to show it on the homepage."
        >
            <Button as-child><Link :href="create()">New sponsor</Link></Button>
        </EmptyState>

        <div v-else class="rounded-lg border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead class="w-10"></TableHead>
                        <TableHead class="w-28">Logo</TableHead>
                        <TableHead>Name</TableHead>
                        <TableHead class="hidden md:table-cell"
                            >Website</TableHead
                        >
                        <TableHead>Status</TableHead>
                        <TableHead class="text-right">Actions</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody ref="container">
                    <TableRow
                        v-for="(sponsor, position) in orderedSponsors"
                        :key="sponsor.id"
                    >
                        <TableCell>
                            <DragHandle
                                :label="sponsor.name"
                                @move="move(position, $event)"
                            />
                        </TableCell>
                        <TableCell>
                            <div
                                class="flex h-10 w-24 items-center justify-center rounded bg-muted/60 p-1"
                            >
                                <img
                                    :src="sponsor.logo_url"
                                    :alt="sponsor.name"
                                    class="max-h-full max-w-full object-contain"
                                />
                            </div>
                        </TableCell>
                        <TableCell class="font-medium">
                            <Link
                                :href="edit(sponsor)"
                                class="hover:underline"
                                >{{ sponsor.name }}</Link
                            >
                        </TableCell>
                        <TableCell class="hidden max-w-xs md:table-cell">
                            <a
                                v-if="sponsor.website_url"
                                :href="sponsor.website_url"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="inline-flex max-w-full items-center gap-1 text-muted-foreground hover:underline"
                            >
                                <span class="truncate">{{
                                    sponsor.website_url
                                }}</span>
                                <ExternalLink class="size-3 shrink-0" />
                            </a>
                            <span v-else class="text-muted-foreground">—</span>
                        </TableCell>
                        <TableCell>
                            <StatusBadge
                                :status="
                                    sponsor.is_active ? 'active' : 'inactive'
                                "
                            />
                        </TableCell>
                        <TableCell class="text-right">
                            <div class="flex justify-end gap-1">
                                <Button as-child variant="ghost" size="icon-sm">
                                    <Link
                                        :href="edit(sponsor)"
                                        aria-label="Edit"
                                        ><Pencil class="size-4"
                                    /></Link>
                                </Button>
                                <ConfirmDelete
                                    :href="destroy(sponsor)"
                                    size="icon-sm"
                                    title="Delete this sponsor?"
                                    :description="`${sponsor.name}'s logo will be removed from the homepage.`"
                                />
                            </div>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <Pagination :paginator="sponsors" />
    </div>
</template>
