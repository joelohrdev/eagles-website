<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { CampPreview } from '@/types/camps';

/**
 * Sticky bar on the staff-only camp preview pages, so nobody mistakes a
 * preview (often of a draft) for the live page.
 */
defineProps<{
    preview: CampPreview;
    /** What this page lets staff do, e.g. "Try the options — nothing is saved." */
    note?: string;
}>();
</script>

<template>
    <div
        class="sticky top-0 z-40 border-b border-amber-300 bg-amber-100 text-amber-950"
        role="status"
    >
        <div
            class="container-site flex flex-wrap items-center justify-between gap-3 py-2.5 text-sm"
        >
            <p>
                <strong>Preview.</strong>
                {{
                    preview.is_draft
                        ? 'This camp is a draft — the public can’t see it yet.'
                        : 'This is how the live page looks.'
                }}
                <template v-if="note"> {{ note }}</template>
            </p>
            <Link
                :href="preview.edit_url"
                class="font-semibold underline underline-offset-2"
                >Back to editing</Link
            >
        </div>
    </div>
</template>
