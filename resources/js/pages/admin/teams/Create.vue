<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import TeamForm from '@/components/admin/teams/TeamForm.vue';
import Heading from '@/components/Heading.vue';
import { create, index } from '@/routes/admin/teams';
import type { CoachOption } from '@/types/teams';

defineProps<{
    coaches: CoachOption[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Teams', href: index() },
            { title: 'New team', href: create() },
        ],
    },
});
</script>

<template>
    <Head title="New team" />

    <div class="flex flex-col gap-6 p-4 md:p-6">
        <Heading
            title="New team"
            description="Add a team to the public Teams page."
        />
        <TeamForm :coaches="coaches" />
    </div>
</template>
