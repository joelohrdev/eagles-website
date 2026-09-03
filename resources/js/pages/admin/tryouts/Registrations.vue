<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Download, Search } from '@lucide/vue';
import { ref } from 'vue';
import ConfirmDelete from '@/components/admin/ConfirmDelete.vue';
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
import { formatDate, formatDateTime } from '@/lib/format';
import { edit, index } from '@/routes/admin/tryouts';
import {
    destroy,
    exportMethod,
    index as registrationsIndex,
} from '@/routes/admin/tryouts/registrations';
import type { Paginated } from '@/types';
import type { Tryout, TryoutRegistration } from '@/types/tryouts';

const props = defineProps<{
    tryout: Tryout;
    registrations: Paginated<TryoutRegistration>;
    filters: { q: string };
}>();

defineOptions({
    layout: (props: { tryout: Tryout }) => ({
        breadcrumbs: [
            { title: 'Tryouts', href: index() },
            { title: props.tryout.title, href: edit(props.tryout.slug) },
            {
                title: 'Registrations',
                href: registrationsIndex(props.tryout.slug),
            },
        ],
    }),
});

const q = ref(props.filters.q ?? '');

function search() {
    router.get(
        registrationsIndex(props.tryout.slug).url,
        { q: q.value || undefined },
        { preserveState: true, replace: true },
    );
}
</script>

<template>
    <Head :title="`Registrations — ${tryout.title}`" />

    <div class="p-6">
        <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
            <div>
                <Heading
                    :title="`${tryout.title} — Registrations`"
                    :description="`${tryout.registrations_count ?? registrations.total} registered${tryout.capacity ? ` · ${tryout.spots_remaining} of ${tryout.capacity} spots left` : ''} · ${formatDateTime(tryout.event_at)}`"
                    class="mb-0"
                />
                <StatusBadge :status="tryout.registration_state" class="mt-2" />
            </div>
            <div class="flex items-center gap-2">
                <Button as-child variant="outline">
                    <a :href="exportMethod.url(tryout.slug)"
                        ><Download class="size-4" /> Export CSV</a
                    >
                </Button>
                <Button as-child variant="ghost">
                    <Link :href="edit(tryout.slug)">Edit tryout</Link>
                </Button>
            </div>
        </div>

        <form
            class="mb-4 flex max-w-md items-center gap-2"
            @submit.prevent="search"
        >
            <div class="relative flex-1">
                <Search
                    class="pointer-events-none absolute top-1/2 left-2.5 size-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    v-model="q"
                    name="q"
                    type="search"
                    placeholder="Search by player, parent, or email"
                    class="pl-8"
                />
            </div>
            <Button type="submit" variant="secondary">Search</Button>
        </form>

        <EmptyState
            v-if="registrations.data.length === 0"
            :title="filters.q ? 'No matches' : 'No registrations yet'"
            :description="
                filters.q
                    ? 'Try a different search.'
                    : 'Registrations will appear here as families sign up.'
            "
        />

        <div v-else class="space-y-4">
            <div class="overflow-x-auto rounded-lg border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Player</TableHead>
                            <TableHead>Parent / Guardian</TableHead>
                            <TableHead>Contact</TableHead>
                            <TableHead>Team / Position</TableHead>
                            <TableHead>Registered</TableHead>
                            <TableHead class="w-16"
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
                                    {{ registration.player_first_name }}
                                    {{ registration.player_last_name }}
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
                                    v-if="registration.notes"
                                    class="mt-1 max-w-xs text-xs text-muted-foreground italic"
                                >
                                    {{ registration.notes }}
                                </p>
                            </TableCell>
                            <TableCell>{{
                                registration.parent_name
                            }}</TableCell>
                            <TableCell>
                                <a
                                    :href="`mailto:${registration.email}`"
                                    class="block hover:underline"
                                    >{{ registration.email }}</a
                                >
                                <a
                                    :href="`tel:${registration.phone}`"
                                    class="block text-xs text-muted-foreground hover:underline"
                                    >{{ registration.phone }}</a
                                >
                            </TableCell>
                            <TableCell>
                                <p>{{ registration.current_team ?? '—' }}</p>
                                <p class="text-xs text-muted-foreground">
                                    {{ registration.primary_position ?? '—' }}
                                </p>
                            </TableCell>
                            <TableCell class="whitespace-nowrap">{{
                                formatDateTime(registration.registered_at)
                            }}</TableCell>
                            <TableCell>
                                <ConfirmDelete
                                    :href="
                                        destroy([tryout.slug, registration.id])
                                    "
                                    size="icon-sm"
                                    title="Remove this registration?"
                                    description="The family will not be notified."
                                    label="Remove"
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
