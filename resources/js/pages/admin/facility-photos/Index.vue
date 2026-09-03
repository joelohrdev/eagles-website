<script setup lang="ts">
import { Form, Head, router } from '@inertiajs/vue3';
import { ArrowDown, ArrowUp, Upload } from '@lucide/vue';
import FacilityPhotoController from '@/actions/App/Http/Controllers/Admin/FacilityPhotoController';
import ConfirmDelete from '@/components/admin/ConfirmDelete.vue';
import EmptyState from '@/components/admin/EmptyState.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { destroy, index, reorder } from '@/routes/admin/facility-photos';
import type { FacilityPhoto } from '@/types/teams';

const props = defineProps<{
    photos: FacilityPhoto[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Facility Photos', href: index() }],
    },
});

function move(fromIndex: number, direction: -1 | 1) {
    const toIndex = fromIndex + direction;

    if (toIndex < 0 || toIndex >= props.photos.length) {
        return;
    }

    const order = props.photos.map((photo) => photo.id);
    [order[fromIndex], order[toIndex]] = [order[toIndex], order[fromIndex]];

    router.post(reorder.url(), { order }, { preserveScroll: true });
}
</script>

<template>
    <Head title="Facility Photos" />

    <div class="flex flex-col gap-8 p-4 md:p-6">
        <Heading
            title="Facility photos"
            description="Photos shown in the gallery on the public Facility page. Reorder with the arrows."
        />

        <Form
            v-bind="FacilityPhotoController.store.form()"
            class="rounded-lg border bg-card p-4"
            v-slot="{ errors, processing }"
        >
            <div class="grid content-start gap-2">
                <Label for="photos">Upload photos</Label>
                <input
                    id="photos"
                    type="file"
                    name="photos[]"
                    multiple
                    accept="image/jpeg,image/png,image/webp"
                    class="block w-full max-w-md text-sm text-muted-foreground file:mr-3 file:rounded-md file:border-0 file:bg-secondary file:px-3 file:py-2 file:text-sm file:font-medium file:text-secondary-foreground hover:file:bg-secondary/80"
                />
                <p class="text-xs text-muted-foreground">
                    Select up to 20 images at a time. JPG, PNG, or WebP up to 8
                    MB each.
                </p>
                <InputError :message="errors.photos ?? errors['photos.0']" />
            </div>
            <Button class="mt-4" :disabled="processing"
                ><Upload class="size-4" /> Upload</Button
            >
        </Form>

        <EmptyState
            v-if="photos.length === 0"
            title="No photos yet"
            description="Upload photos of the facility to build the public gallery."
        />

        <div
            v-else
            class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
        >
            <div
                v-for="(photo, i) in photos"
                :key="photo.id"
                class="flex flex-col overflow-hidden rounded-lg border bg-card"
            >
                <img
                    :src="photo.thumbnail_url"
                    :alt="photo.caption ?? ''"
                    class="aspect-[3/2] w-full object-cover"
                    loading="lazy"
                />
                <div class="flex flex-1 flex-col gap-2 p-3">
                    <Form
                        v-bind="FacilityPhotoController.update.form(photo.id)"
                        class="flex gap-2"
                        :options="{ preserveScroll: true }"
                        v-slot="{ errors, processing }"
                    >
                        <div class="grid flex-1 gap-1">
                            <Input
                                name="caption"
                                :default-value="photo.caption ?? ''"
                                placeholder="Caption (optional)"
                                aria-label="Caption"
                            />
                            <InputError :message="errors.caption" />
                        </div>
                        <Button
                            size="sm"
                            variant="secondary"
                            :disabled="processing"
                            >Save</Button
                        >
                    </Form>
                    <div class="flex items-center justify-between">
                        <div class="flex gap-1">
                            <Button
                                type="button"
                                variant="outline"
                                size="icon-sm"
                                :disabled="i === 0"
                                aria-label="Move up"
                                @click="move(i, -1)"
                            >
                                <ArrowUp class="size-4" />
                            </Button>
                            <Button
                                type="button"
                                variant="outline"
                                size="icon-sm"
                                :disabled="i === photos.length - 1"
                                aria-label="Move down"
                                @click="move(i, 1)"
                            >
                                <ArrowDown class="size-4" />
                            </Button>
                        </div>
                        <ConfirmDelete
                            :href="destroy(photo.id)"
                            size="icon-sm"
                            title="Delete this photo?"
                            description="It will be removed from the facility gallery."
                        />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
