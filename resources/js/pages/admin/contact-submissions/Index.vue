<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import ConfirmDelete from '@/components/admin/ConfirmDelete.vue';
import EmptyState from '@/components/admin/EmptyState.vue';
import Pagination from '@/components/admin/Pagination.vue';
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
import { destroy, index, show } from '@/routes/admin/contact-submissions';
import type { Paginated } from '@/types';

type Submission = {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    subject: string | null;
    message: string;
    read_at: string | null;
    created_at: string;
};

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Contact Inbox', href: index() }],
    },
});

defineProps<{
    submissions: Paginated<Submission>;
    filter: string;
    unreadCount: number;
}>();
</script>

<template>
    <Head title="Contact Inbox" />

    <div class="flex flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <Heading
                title="Contact Inbox"
                description="Messages sent through the public contact form."
            />
            <div class="flex items-center gap-2">
                <Button
                    as-child
                    :variant="filter === '' ? 'default' : 'outline'"
                    size="sm"
                >
                    <Link :href="index()">All</Link>
                </Button>
                <Button
                    as-child
                    :variant="filter === 'unread' ? 'default' : 'outline'"
                    size="sm"
                >
                    <Link :href="index({ query: { filter: 'unread' } })">
                        Unread
                        <Badge variant="secondary" class="ml-1">{{
                            unreadCount
                        }}</Badge>
                    </Link>
                </Button>
            </div>
        </div>

        <EmptyState
            v-if="!submissions.data.length"
            title="No messages"
            description="Messages from the contact form will appear here."
        />

        <div v-else class="rounded-lg border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>From</TableHead>
                        <TableHead>Subject</TableHead>
                        <TableHead class="hidden md:table-cell"
                            >Preview</TableHead
                        >
                        <TableHead>Received</TableHead>
                        <TableHead class="w-24" />
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow
                        v-for="submission in submissions.data"
                        :key="submission.id"
                        :class="{ 'bg-accent/40': !submission.read_at }"
                    >
                        <TableCell>
                            <Link :href="show(submission.id)" class="block">
                                <span
                                    :class="
                                        submission.read_at
                                            ? 'font-normal'
                                            : 'font-semibold'
                                    "
                                    >{{ submission.name }}</span
                                >
                                <span
                                    class="block text-xs text-muted-foreground"
                                    >{{ submission.email }}</span
                                >
                            </Link>
                        </TableCell>
                        <TableCell>
                            <Link :href="show(submission.id)">{{
                                submission.subject || 'No subject'
                            }}</Link>
                        </TableCell>
                        <TableCell
                            class="hidden max-w-xs truncate text-muted-foreground md:table-cell"
                        >
                            {{ submission.message }}
                        </TableCell>
                        <TableCell
                            class="whitespace-nowrap text-muted-foreground"
                            >{{
                                formatDateTime(submission.created_at)
                            }}</TableCell
                        >
                        <TableCell class="text-right">
                            <ConfirmDelete
                                :href="destroy(submission.id)"
                                size="icon-sm"
                                title="Delete this message?"
                                :description="`The message from ${submission.name} will be permanently deleted.`"
                            />
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <Pagination :paginator="submissions" />
    </div>
</template>
