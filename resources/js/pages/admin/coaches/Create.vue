<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import CoachForm from '@/components/admin/coaches/CoachForm.vue';
import Heading from '@/components/Heading.vue';
import { create, index } from '@/routes/admin/coaches';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Coaches', href: index() },
            { title: 'New coach', href: create() },
        ],
    },
});
</script>

<template>
    <Head title="New coach" />

    <div class="flex flex-col gap-6 p-4 md:p-6">
        <Heading
            title="New coach"
            description="Add a member of the coaching staff."
        />
        <CoachForm />
    </div>
</template>
