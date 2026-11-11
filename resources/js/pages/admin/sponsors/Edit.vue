<script setup lang="ts">
import { Head, setLayoutProps } from '@inertiajs/vue3';
import SponsorForm from '@/components/admin/sponsors/SponsorForm.vue';
import Heading from '@/components/Heading.vue';
import { edit, index } from '@/routes/admin/sponsors';
import type { Sponsor } from '@/types/sponsors';

const props = defineProps<{
    sponsor: Sponsor;
}>();

setLayoutProps({
    breadcrumbs: [
        { title: 'Sponsors', href: index() },
        { title: props.sponsor.name, href: edit(props.sponsor) },
    ],
});
</script>

<template>
    <Head :title="`Edit ${sponsor.name}`" />

    <div class="flex flex-col gap-6 p-4 md:p-6">
        <Heading
            :title="sponsor.name"
            description="Update this sponsor's logo and website."
        />
        <SponsorForm :sponsor="sponsor" />
    </div>
</template>
