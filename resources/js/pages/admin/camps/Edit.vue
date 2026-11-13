<script setup lang="ts">
import { Head, Link, setLayoutProps } from '@inertiajs/vue3';
import { Archive, ArchiveRestore, ExternalLink, Eye, Users } from '@lucide/vue';
import ConfirmDelete from '@/components/admin/ConfirmDelete.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { formatDateTime } from '@/lib/format';
import { archive, destroy, edit, index, unarchive } from '@/routes/admin/camps';
import { index as registrationsIndex } from '@/routes/admin/camps/registrations';
import type { AdminCamp, CampSeo } from '@/types/camps';
import CampForm from './CampForm.vue';

const props = defineProps<{
    camp: AdminCamp;
    seo: CampSeo;
}>();

setLayoutProps({
    breadcrumbs: [
        { title: 'Camps', href: index() },
        { title: props.camp.name, href: edit(props.camp.slug) },
    ],
});
</script>

<template>
    <Head :title="`Edit ${camp.name}`" />

    <div class="p-4 md:p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <Heading
                :title="camp.name"
                description="Update camp details, pricing, registration window, and sharing."
            />
            <div class="flex items-center gap-2">
                <Button as-child variant="outline" size="sm">
                    <Link :href="registrationsIndex(camp.slug)"
                        ><Users class="size-4" /> Registrations</Link
                    >
                </Button>
                <Button as-child variant="outline" size="sm">
                    <a :href="camp.preview_url" target="_blank" rel="noopener"
                        ><Eye class="size-4" /> Preview</a
                    >
                </Button>
                <Button
                    v-if="camp.is_published"
                    as-child
                    variant="outline"
                    size="sm"
                >
                    <a :href="camp.public_url" target="_blank" rel="noopener"
                        ><ExternalLink class="size-4" /> View live</a
                    >
                </Button>
            </div>
        </div>
        <div
            v-if="camp.archived_at"
            class="mb-6 flex max-w-4xl flex-wrap items-center justify-between gap-3 rounded-md border border-dashed p-4"
        >
            <p class="text-sm">
                <span class="font-medium">Archived</span>
                {{ formatDateTime(camp.archived_at) }}. It’s hidden from the
                site and kept with its registrations. Unarchive it to publish it
                again.
            </p>
            <Button as-child variant="outline" size="sm">
                <Link :href="unarchive(camp.slug)" method="delete" as="button"
                    ><ArchiveRestore class="size-4" /> Unarchive</Link
                >
            </Button>
        </div>
        <div class="max-w-4xl">
            <CampForm :camp="camp" :seo="seo">
                <template #actions>
                    <Button
                        v-if="!camp.archived_at"
                        as-child
                        variant="outline"
                        size="sm"
                    >
                        <Link
                            :href="archive(camp.slug)"
                            method="post"
                            as="button"
                            ><Archive class="size-4" /> Archive</Link
                        >
                    </Button>
                    <ConfirmDelete
                        v-if="camp.can_delete"
                        :href="destroy(camp.slug)"
                        title="Delete this camp?"
                        description="Nobody has registered, so nothing else is lost. This cannot be undone."
                    />
                </template>
            </CampForm>
        </div>
    </div>
</template>
