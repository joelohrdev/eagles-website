<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Mail, MailOpen, Reply } from '@lucide/vue';
import ConfirmDelete from '@/components/admin/ConfirmDelete.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { formatDateTime } from '@/lib/format';
import { destroy, index, update } from '@/routes/admin/contact-submissions';

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

const props = defineProps<{ submission: Submission }>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Contact Inbox', href: index() },
            { title: 'Message', href: '#' },
        ],
    },
});

const replyHref = `mailto:${props.submission.email}?subject=${encodeURIComponent('Re: ' + (props.submission.subject || 'Your message to Eagles Baseball Travel'))}`;
</script>

<template>
    <Head :title="submission.subject || `Message from ${submission.name}`" />

    <div class="flex flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <Button as-child variant="ghost" size="sm" class="mb-2 -ml-2">
                    <Link :href="index()"
                        ><ArrowLeft class="size-4" /> Back to inbox</Link
                    >
                </Button>
                <Heading
                    :title="submission.subject || 'No subject'"
                    :description="`Received ${formatDateTime(submission.created_at)}`"
                />
            </div>
            <div class="flex items-center gap-2">
                <Button as-child variant="outline" size="sm">
                    <a :href="replyHref"
                        ><Reply class="size-4" /> Reply by email</a
                    >
                </Button>
                <Button as-child variant="outline" size="sm">
                    <Link
                        :href="update(submission.id)"
                        method="patch"
                        :data="{ read: submission.read_at ? 0 : 1 }"
                        as="button"
                        preserve-scroll
                    >
                        <MailOpen v-if="!submission.read_at" class="size-4" />
                        <Mail v-else class="size-4" />
                        {{ submission.read_at ? 'Mark unread' : 'Mark read' }}
                    </Link>
                </Button>
                <ConfirmDelete
                    :href="destroy(submission.id)"
                    title="Delete this message?"
                />
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="rounded-lg border bg-card p-6 lg:col-span-2">
                <p class="leading-relaxed whitespace-pre-line">
                    {{ submission.message }}
                </p>
            </div>
            <aside class="rounded-lg border bg-card p-6 text-sm">
                <h2 class="font-semibold">Sender</h2>
                <dl class="mt-3 space-y-2">
                    <div>
                        <dt class="text-muted-foreground">Name</dt>
                        <dd>{{ submission.name }}</dd>
                    </div>
                    <div>
                        <dt class="text-muted-foreground">Email</dt>
                        <dd>
                            <a
                                :href="`mailto:${submission.email}`"
                                class="text-sky hover:underline"
                                >{{ submission.email }}</a
                            >
                        </dd>
                    </div>
                    <div v-if="submission.phone">
                        <dt class="text-muted-foreground">Phone</dt>
                        <dd>
                            <a
                                :href="`tel:${submission.phone}`"
                                class="text-sky hover:underline"
                                >{{ submission.phone }}</a
                            >
                        </dd>
                    </div>
                </dl>
            </aside>
        </div>
    </div>
</template>
