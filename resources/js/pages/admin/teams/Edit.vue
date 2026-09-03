<script setup lang="ts">
import { Head, setLayoutProps } from '@inertiajs/vue3';
import TeamForm from '@/components/admin/teams/TeamForm.vue';
import Heading from '@/components/Heading.vue';
import { edit, index } from '@/routes/admin/teams';
import type { CoachOption, SeoMetaProp, Team } from '@/types/teams';

const props = defineProps<{
    team: Team;
    seo: SeoMetaProp;
    coaches: CoachOption[];
}>();

setLayoutProps({
    breadcrumbs: [
        { title: 'Teams', href: index() },
        { title: props.team.name, href: edit(props.team) },
    ],
});
</script>

<template>
    <Head :title="`Edit ${team.name}`" />

    <div class="flex flex-col gap-6 p-4 md:p-6">
        <Heading
            :title="team.name"
            description="Update this team's details, photo, and sharing settings."
        />
        <TeamForm :team="team" :seo="seo" :coaches="coaches" />
    </div>
</template>
