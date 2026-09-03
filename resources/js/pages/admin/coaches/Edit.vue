<script setup lang="ts">
import { Head, setLayoutProps } from '@inertiajs/vue3';
import CoachForm from '@/components/admin/coaches/CoachForm.vue';
import Heading from '@/components/Heading.vue';
import { edit, index } from '@/routes/admin/coaches';
import type { Coach, SeoMetaProp } from '@/types/teams';

const props = defineProps<{
    coach: Coach;
    seo: SeoMetaProp;
}>();

setLayoutProps({
    breadcrumbs: [
        { title: 'Coaches', href: index() },
        { title: props.coach.name, href: edit(props.coach) },
    ],
});
</script>

<template>
    <Head :title="`Edit ${coach.name}`" />

    <div class="flex flex-col gap-6 p-4 md:p-6">
        <Heading
            :title="coach.name"
            description="Update this coach's details, headshot, and sharing settings."
        />
        <CoachForm :coach="coach" :seo="seo" />
    </div>
</template>
