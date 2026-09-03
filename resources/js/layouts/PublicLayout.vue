<script setup lang="ts">
import SeoHead from '@/components/site/SeoHead.vue';
import SiteFooter from '@/components/site/SiteFooter.vue';
import SiteHeader from '@/components/site/SiteHeader.vue';
import { Toaster } from '@/components/ui/sonner';
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
        <SiteHeader />
        <main id="main" class="flex-1">
            <slot />
        </main>
        <SiteFooter />
        <Toaster />
    </div>
</template>
