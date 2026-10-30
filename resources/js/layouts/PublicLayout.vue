<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import SeoHead from '@/components/site/SeoHead.vue';
import SiteFooter from '@/components/site/SiteFooter.vue';
import SiteHeader from '@/components/site/SiteHeader.vue';
import { Toaster } from '@/components/ui/sonner';

const page = usePage();
</script>

<template>
    <div class="flex min-h-screen flex-col bg-background text-foreground">
        <SeoHead />
        <a
            href="#main"
            class="sr-only focus:not-sr-only focus:absolute focus:top-2 focus:left-2 focus:z-50 focus:rounded-md focus:bg-sky focus:px-3 focus:py-2 focus:text-navy"
        >
            Skip to content
        </a>
        <p
            v-if="page.props.pagePreview"
            class="bg-sky px-4 py-2 text-center text-sm font-medium text-navy"
            role="status"
        >
            Preview: the {{ page.props.pagePreview }} page is not live yet. Only
            people with the preview link can see it.
        </p>
        <SiteHeader />
        <main id="main" class="flex-1">
            <slot />
        </main>
        <SiteFooter />
        <Toaster />
    </div>
</template>
