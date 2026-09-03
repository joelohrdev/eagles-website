<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ExternalLink, Users } from '@lucide/vue';
import TryoutController from '@/actions/App/Http/Controllers/Admin/TryoutController';
import ConfirmDelete from '@/components/admin/ConfirmDelete.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { destroy, edit, index } from '@/routes/admin/tryouts';
import { index as registrationsIndex } from '@/routes/admin/tryouts/registrations';
import type { SeoMetaForm } from '@/types';
import type { Tryout } from '@/types/tryouts';
import TryoutForm from './TryoutForm.vue';

defineProps<{
    tryout: Tryout;
    seo: SeoMetaForm | null;
    publicUrl: string;
}>();

defineOptions({
    layout: (props: { tryout: Tryout }) => ({
        breadcrumbs: [
            { title: 'Tryouts', href: index() },
            { title: props.tryout.title, href: edit(props.tryout.slug) },
        ],
    }),
});
</script>

<template>
    <Head :title="`Edit ${tryout.title}`" />

    <div class="p-6">
        <div class="mb-8 flex flex-wrap items-start justify-between gap-4">
            <Heading
                :title="tryout.title"
                :description="`${tryout.division} · ${tryout.registrations_count ?? 0} registrations`"
                class="mb-0"
            />
            <div class="flex flex-wrap items-center gap-2">
                <Button as-child variant="outline" size="sm">
                    <Link :href="registrationsIndex(tryout.slug)">
                        <Users class="size-4" /> Registrations
                    </Link>
                </Button>
                <Button as-child variant="outline" size="sm">
                    <a :href="publicUrl" target="_blank" rel="noopener">
                        <ExternalLink class="size-4" /> View public page
                    </a>
                </Button>
                <ConfirmDelete
                    :href="destroy(tryout.slug)"
                    title="Delete this tryout?"
                    description="All registrations for this tryout will be deleted too."
                />
            </div>
        </div>

        <Form
            v-bind="TryoutController.update.form(tryout.slug)"
            v-slot="{ errors, processing }"
            class="space-y-6"
        >
            <TryoutForm
                :tryout="tryout"
                :seo="seo"
                :errors="errors"
                :public-url="publicUrl"
            />

            <div class="flex items-center gap-3 border-t pt-6">
                <Button :disabled="processing">Save changes</Button>
                <Button as-child variant="ghost">
                    <Link :href="index()">Back to tryouts</Link>
                </Button>
            </div>
        </Form>
    </div>
</template>
