<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import SponsorForm from '@/components/admin/sponsors/SponsorForm.vue';
import Heading from '@/components/Heading.vue';
import { create, index } from '@/routes/admin/sponsors';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Sponsors', href: index() },
            { title: 'New sponsor', href: create() },
        ],
    },
});
</script>

<template>
    <Head title="New sponsor" />

    <div class="flex flex-col gap-6 p-4 md:p-6">
        <Heading
            title="New sponsor"
            description="Add a sponsor's logo to the homepage."
        />
        <SponsorForm />
    </div>
</template>
