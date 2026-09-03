<script setup lang="ts">
import { Check, Link2, Share2 } from '@lucide/vue';
import { computed, onMounted, ref } from 'vue';
import FacebookIcon from '@/components/site/icons/FacebookIcon.vue';
import XIcon from '@/components/site/icons/XIcon.vue';
import { Button } from '@/components/ui/button';

const props = defineProps<{
    url: string;
    title: string;
    text?: string;
}>();

const canNativeShare = ref(false);
const copied = ref(false);

onMounted(() => {
    canNativeShare.value =
        typeof navigator !== 'undefined' &&
        typeof navigator.share === 'function';
});

const facebookUrl = computed(
    () =>
        `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(props.url)}`,
);
const xUrl = computed(
    () =>
        `https://twitter.com/intent/tweet?url=${encodeURIComponent(props.url)}&text=${encodeURIComponent(props.title)}`,
);

async function nativeShare() {
    try {
        await navigator.share({
            title: props.title,
            text: props.text ?? props.title,
            url: props.url,
        });
    } catch {
        // user cancelled
    }
}

async function copyLink() {
    try {
        await navigator.clipboard.writeText(props.url);
        copied.value = true;
        setTimeout(() => (copied.value = false), 2000);
    } catch {
        // clipboard unavailable
    }
}
</script>

<template>
    <div class="flex flex-wrap items-center gap-2">
        <span class="text-sm font-medium text-muted-foreground">Share:</span>
        <Button
            v-if="canNativeShare"
            type="button"
            variant="outline"
            size="sm"
            @click="nativeShare"
        >
            <Share2 class="size-4" /> Share
        </Button>
        <template v-else>
            <Button as-child variant="outline" size="sm">
                <a
                    :href="facebookUrl"
                    target="_blank"
                    rel="noopener"
                    aria-label="Share on Facebook"
                >
                    <FacebookIcon class="size-4" /> Facebook
                </a>
            </Button>
            <Button as-child variant="outline" size="sm">
                <a
                    :href="xUrl"
                    target="_blank"
                    rel="noopener"
                    aria-label="Share on X"
                >
                    <XIcon class="size-4" /> Post
                </a>
            </Button>
        </template>
        <Button type="button" variant="outline" size="sm" @click="copyLink">
            <Check v-if="copied" class="size-4" />
            <Link2 v-else class="size-4" />
            {{ copied ? 'Copied' : 'Copy link' }}
        </Button>
    </div>
</template>
