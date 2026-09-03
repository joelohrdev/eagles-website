<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
    url: string;
    title?: string;
}>();

/** Accepts watch, youtu.be, shorts, and embed URLs. */
const videoId = computed(() => {
    try {
        const u = new URL(props.url);

        if (u.hostname.includes('youtu.be')) {
            return u.pathname.slice(1);
        }

        if (
            u.pathname.startsWith('/embed/') ||
            u.pathname.startsWith('/shorts/')
        ) {
            return u.pathname.split('/')[2] ?? null;
        }

        return u.searchParams.get('v');
    } catch {
        return null;
    }
});
</script>

<template>
    <div
        v-if="videoId"
        class="aspect-video w-full overflow-hidden rounded-lg bg-navy"
    >
        <iframe
            :src="`https://www.youtube-nocookie.com/embed/${videoId}`"
            :title="title ?? 'Video'"
            class="size-full"
            loading="lazy"
            allow="
                accelerometer;
                autoplay;
                clipboard-write;
                encrypted-media;
                gyroscope;
                picture-in-picture;
                web-share;
            "
            referrerpolicy="strict-origin-when-cross-origin"
            allowfullscreen
        />
    </div>
</template>
