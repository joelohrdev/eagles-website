<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import SeoMetaController from '@/actions/App/Http/Controllers/Admin/SeoMetaController';
import SeoFields from '@/components/admin/SeoFields.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { index } from '@/routes/admin/seo';
import type { SeoMetaForm } from '@/types';

const props = defineProps<{
    routeKey: string;
    page: { label: string; url: string };
    seo: SeoMetaForm | null;
    fallback: {
        title: string;
        description: string | null;
        image_url: string | null;
        url: string;
    };
    siteName: string;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'SEO & Sharing', href: index() },
            { title: 'Edit', href: '#' },
        ],
    },
});
</script>

<template>
    <Head :title="`SEO: ${page.label}`" />

    <div class="flex flex-col gap-6 p-4 md:p-6">
        <div>
            <Button as-child variant="ghost" size="sm" class="mb-2 -ml-2">
                <Link :href="index()"
                    ><ArrowLeft class="size-4" /> All pages</Link
                >
            </Button>
            <Heading :title="`${page.label} page`" :description="page.url" />
        </div>

        <Form
            v-bind="SeoMetaController.update.form(props.routeKey)"
            class="space-y-6"
            v-slot="{ errors, processing }"
        >
            <SeoFields
                :seo="seo"
                :errors="errors"
                :fallback="fallback"
                :site-name="siteName"
            />

            <div class="flex items-center gap-3 border-t pt-6">
                <Button :disabled="processing">Save SEO settings</Button>
                <Button as-child variant="outline">
                    <a :href="page.url" target="_blank" rel="noopener"
                        >Preview page</a
                    >
                </Button>
            </div>
        </Form>
    </div>
</template>
