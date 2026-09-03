<script setup lang="ts">
import { computed, ref } from 'vue';
import FormTabs from '@/components/admin/FormTabs.vue';
import ImageUpload from '@/components/admin/ImageUpload.vue';
import SeoFields from '@/components/admin/SeoFields.vue';
import DateTimePicker from '@/components/DateTimePicker.vue';
import InputError from '@/components/InputError.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Textarea } from '@/components/ui/textarea';
import type { SeoMetaForm } from '@/types';
import type { Tryout } from '@/types/tryouts';

const props = withDefaults(
    defineProps<{
        tryout?: Tryout | null;
        seo?: SeoMetaForm | null;
        errors: Record<string, string>;
        publicUrl?: string;
    }>(),
    { tryout: null, seo: null, publicUrl: undefined },
);

const isPublished = ref(props.tryout?.is_published ?? false);
const title = ref(props.tryout?.title ?? '');
const description = ref(props.tryout?.description ?? '');

const seoFallback = computed(() => ({
    title: title.value || 'Tryout title',
    description:
        description.value ||
        'Tryout date, location, division, and registration details.',
    image_url: props.tryout?.image_url ?? null,
    url: props.publicUrl,
}));
</script>

<template>
    <FormTabs
        :errors="errors"
        :tabs="[
            { value: 'details' },
            {
                value: 'registration',
                fields: [
                    'registration_opens_at',
                    'registration_closes_at',
                    'capacity',
                ],
            },
            { value: 'seo', prefix: 'seo' },
        ]"
        class="w-full"
    >
        <TabsList>
            <TabsTrigger value="details">Details</TabsTrigger>
            <TabsTrigger value="registration">Registration</TabsTrigger>
            <TabsTrigger value="seo">SEO &amp; Sharing</TabsTrigger>
        </TabsList>

        <TabsContent value="details" class="mt-6 space-y-6">
            <div class="grid gap-6 md:grid-cols-2">
                <div class="grid content-start gap-2">
                    <Label for="title">Title</Label>
                    <Input
                        id="title"
                        v-model="title"
                        name="title"
                        required
                        placeholder="13U Tryouts"
                    />
                    <InputError :message="errors.title" />
                </div>
                <div class="grid content-start gap-2">
                    <Label for="division">Division</Label>
                    <Input
                        id="division"
                        name="division"
                        :default-value="tryout?.division ?? ''"
                        required
                        placeholder="13U"
                    />
                    <InputError :message="errors.division" />
                </div>
            </div>

            <div class="grid gap-6 md:grid-cols-2">
                <DateTimePicker
                    name="event_at"
                    label="Date & time"
                    :model-value="tryout?.event_at"
                    required
                    :error="errors.event_at"
                />
                <div class="grid content-start gap-2">
                    <Label for="location">Location</Label>
                    <Input
                        id="location"
                        name="location"
                        :default-value="tryout?.location ?? ''"
                        placeholder="Eagles Training Facility"
                    />
                    <InputError :message="errors.location" />
                </div>
            </div>

            <div class="grid content-start gap-2">
                <Label for="description">Description</Label>
                <Textarea
                    id="description"
                    v-model="description"
                    name="description"
                    rows="6"
                    placeholder="What to bring, what to expect, who should attend…"
                />
                <InputError :message="errors.description" />
            </div>

            <ImageUpload
                name="image"
                label="Cover image"
                remove-name="remove_image"
                :current-url="tryout?.image_url ?? null"
                :error="errors.image"
            />

            <div class="flex items-center gap-3 rounded-md border p-4">
                <Switch id="is_published" v-model="isPublished" />
                <input
                    type="hidden"
                    name="is_published"
                    :value="isPublished ? 1 : 0"
                />
                <div>
                    <Label for="is_published">Published</Label>
                    <p class="text-xs text-muted-foreground">
                        Unpublished tryouts are hidden from the public site.
                    </p>
                </div>
            </div>
        </TabsContent>

        <TabsContent value="registration" class="mt-6 space-y-6">
            <p class="text-sm text-muted-foreground">
                Registration is free. Families can register only between the
                open and close times below (both optional) and while spots
                remain.
            </p>
            <div class="grid gap-6 md:grid-cols-3">
                <DateTimePicker
                    name="registration_opens_at"
                    label="Registration opens"
                    :model-value="tryout?.registration_opens_at"
                    optional
                    :error="errors.registration_opens_at"
                />
                <DateTimePicker
                    name="registration_closes_at"
                    label="Registration closes"
                    :model-value="tryout?.registration_closes_at"
                    optional
                    :error="errors.registration_closes_at"
                />
                <div class="grid content-start gap-2">
                    <Label for="capacity">Capacity</Label>
                    <Input
                        id="capacity"
                        name="capacity"
                        type="number"
                        min="1"
                        :default-value="tryout?.capacity ?? ''"
                        placeholder="Unlimited"
                    />
                    <InputError :message="errors.capacity" />
                </div>
            </div>
        </TabsContent>

        <TabsContent value="seo" class="mt-6">
            <SeoFields :seo="seo" :errors="errors" :fallback="seoFallback" />
        </TabsContent>
    </FormTabs>
</template>
