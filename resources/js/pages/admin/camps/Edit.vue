<script setup lang="ts">
import { Head, Link, setLayoutProps } from '@inertiajs/vue3';
import { ExternalLink, Users } from '@lucide/vue';
import ConfirmDelete from '@/components/admin/ConfirmDelete.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { destroy, edit, index } from '@/routes/admin/camps';
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
                    <a :href="camp.public_url" target="_blank" rel="noopener"
                        ><ExternalLink class="size-4" /> View</a
                    >
                </Button>
            </div>
        </div>
        <div class="max-w-4xl">
            <CampForm :camp="camp" :seo="seo">
                <template #actions>
                    <ConfirmDelete
                        :href="destroy(camp.slug)"
                        title="Delete this camp?"
                        description="All registrations for this camp will also be deleted. This cannot be undone."
                    />
                </template>
            </CampForm>
        </div>
    </div>
</template>
