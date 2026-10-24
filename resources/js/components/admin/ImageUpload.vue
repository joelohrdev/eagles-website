<script setup lang="ts">
import { ImagePlus, Trash2, Upload } from '@lucide/vue';
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Dropzone } from '@/components/ui/dropzone';
import { Label } from '@/components/ui/label';

/**
 * Drag-and-drop image field with live preview for use inside an Inertia
 * <Form>. Submits the file under `name`; when an existing image is removed
 * it submits `<removeName>=1` so the server can delete it.
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

const dropzone = ref<InstanceType<typeof Dropzone> | null>(null);
const previewUrl = ref<string | null>(props.currentUrl);
const removed = ref(false);

watch(
    () => props.currentUrl,
    (url) => {
        setPreview(url);
        removed.value = false;
    },
);

onBeforeUnmount(() => setPreview(null));

const aspectClass = computed(
    () =>
        ({
            video: 'aspect-video',
            square: 'aspect-square',
            share: 'aspect-[1200/630]',
        })[props.aspect],
);

function setPreview(url: string | null) {
    if (previewUrl.value?.startsWith('blob:')) {
        URL.revokeObjectURL(previewUrl.value);
    }

    previewUrl.value = url;
}

function onSelect(files: File[]) {
    const file = files[0];

    if (!file) {
        return;
    }

    removed.value = false;
    setPreview(URL.createObjectURL(file));
}

function clear() {
    dropzone.value?.clear();
    setPreview(null);
    removed.value = props.currentUrl !== null;
}
</script>

<template>
    <div class="grid content-start gap-2">
        <Label :for="name">{{ label }}</Label>
        <Dropzone
            :id="name"
            ref="dropzone"
            :name="name"
            accept="image/jpeg,image/png,image/webp"
            :invalid="Boolean(error)"
            class="w-full max-w-sm p-0"
            :class="aspectClass"
            @select="onSelect"
        >
            <template #default="{ isDragging }">
                <template v-if="previewUrl">
                    <img
                        :src="previewUrl"
                        alt=""
                        class="absolute inset-0 size-full object-cover"
                    />
                    <div
                        class="absolute inset-0 flex flex-col items-center justify-center gap-1 bg-black/55 text-white opacity-0 transition-opacity hover:opacity-100"
                        :class="{ 'opacity-100': isDragging }"
                    >
                        <Upload class="size-5" />
                        <span class="text-xs font-medium">
                            {{
                                isDragging
                                    ? 'Drop to replace'
                                    : 'Click or drop to replace'
                            }}
                        </span>
                    </div>
                </template>
                <div v-else class="flex flex-col items-center gap-1 px-4">
                    <component
                        :is="isDragging ? Upload : ImagePlus"
                        class="size-6"
                    />
                    <span class="text-xs font-medium text-foreground">
                        {{
                            isDragging
                                ? 'Drop image here'
                                : 'Drag & drop an image'
                        }}
                    </span>
                    <span class="text-xs">or click to browse</span>
                </div>
            </template>
        </Dropzone>
        <div class="flex flex-wrap items-center gap-3">
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
        </div>
        <input
            v-if="removeName && removed"
            type="hidden"
            :name="removeName"
            value="1"
        />
        <InputError :message="error" />
    </div>
</template>
