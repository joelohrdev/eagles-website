<script setup lang="ts">
import { ImagePlus, Trash2 } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';

/**
 * File input with live preview for use inside an Inertia <Form>.
 * Submits the file under `name`; when an existing image is removed it
 * submits `<removeName>=1` so the server can delete it.
 */
const props = withDefaults(
    defineProps<{
        name: string;
        label?: string;
        currentUrl?: string | null;
        removeName?: string;
        error?: string;
        hint?: string;
        aspect?: 'video' | 'square' | 'share';
    }>(),
    {
        label: 'Image',
        currentUrl: null,
        removeName: undefined,
        error: undefined,
        hint: 'JPG, PNG, or WebP up to 5 MB.',
        aspect: 'video',
    },
);

const input = ref<HTMLInputElement | null>(null);
const previewUrl = ref<string | null>(props.currentUrl);
const removed = ref(false);

watch(
    () => props.currentUrl,
    (url) => {
        previewUrl.value = url;
        removed.value = false;
    },
);

const aspectClass = computed(
    () =>
        ({
            video: 'aspect-video',
            square: 'aspect-square',
            share: 'aspect-[1200/630]',
        })[props.aspect],
);

function onChange(event: Event) {
    const file = (event.target as HTMLInputElement).files?.[0];

    if (!file) {
        return;
    }

    removed.value = false;
    previewUrl.value = URL.createObjectURL(file);
}

function clear() {
    if (input.value) {
        input.value.value = '';
    }

    previewUrl.value = null;
    removed.value = props.currentUrl !== null;
}
</script>

<template>
    <div class="grid content-start gap-2">
        <Label :for="name">{{ label }}</Label>
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start">
            <div
                class="relative w-full max-w-xs overflow-hidden rounded-md border bg-muted"
                :class="aspectClass"
            >
                <img
                    v-if="previewUrl"
                    :src="previewUrl"
                    alt=""
                    class="size-full object-cover"
                />
                <div
                    v-else
                    class="flex size-full flex-col items-center justify-center gap-1 text-muted-foreground"
                >
                    <ImagePlus class="size-6" />
                    <span class="text-xs">No image</span>
                </div>
            </div>
            <div class="flex flex-col gap-2">
                <input
                    :id="name"
                    ref="input"
                    type="file"
                    :name="name"
                    accept="image/jpeg,image/png,image/webp"
                    class="block w-full max-w-xs text-sm text-muted-foreground file:mr-3 file:rounded-md file:border-0 file:bg-secondary file:px-3 file:py-2 file:text-sm file:font-medium file:text-secondary-foreground hover:file:bg-secondary/80"
                    @change="onChange"
                />
                <p v-if="hint" class="text-xs text-muted-foreground">
                    {{ hint }}
                </p>
                <Button
                    v-if="previewUrl"
                    type="button"
                    variant="outline"
                    size="sm"
                    class="w-fit"
                    @click="clear"
                >
                    <Trash2 class="size-4" /> Remove image
                </Button>
                <input
                    v-if="removeName && removed"
                    type="hidden"
                    :name="removeName"
                    value="1"
                />
            </div>
        </div>
        <InputError :message="error" />
    </div>
</template>
