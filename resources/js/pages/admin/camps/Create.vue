<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { create, index } from '@/routes/admin/camps';
import CampForm from './CampForm.vue';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Camps', href: index() },
            { title: 'New camp', href: create() },
        ],
    },
});
</script>

<template>
    <Head title="New camp" />

    <div class="p-4 md:p-6">
        <Heading
            title="New camp"
            description="Create a camp or clinic. Registration and pricing are on the second tab."
        />
        <div class="max-w-4xl">
            <CampForm />
        </div>
    </div>
</template>
