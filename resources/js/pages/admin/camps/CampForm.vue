<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import CampController from '@/actions/App/Http/Controllers/Admin/CampController';
import FormTabs from '@/components/admin/FormTabs.vue';
import ImageUpload from '@/components/admin/ImageUpload.vue';
import SeoFields from '@/components/admin/SeoFields.vue';
import DateTimePicker from '@/components/DateTimePicker.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Textarea } from '@/components/ui/textarea';
import type { AdminCamp, CampSeo } from '@/types/camps';

const props = withDefaults(
    defineProps<{
        camp?: AdminCamp | null;
        seo?: CampSeo;
    }>(),
    { camp: null, seo: null },
);

const isPublished = ref(props.camp?.is_published ?? false);

const formProps = computed(() =>
    props.camp
        ? CampController.update.form(props.camp.slug)
        : CampController.store.form(),
);

const seoFallback = computed(() => ({
    title: props.camp?.name ?? 'Camp name',
    description: props.camp
        ? `${props.camp.name}${props.camp.location ? ` at ${props.camp.location}` : ''}. Register online.`
        : 'Auto-generated from the camp details.',
    image_url: props.camp?.image_url ?? null,
    url: props.camp?.public_url,
}));
</script>

<template>
    <Form v-bind="formProps" v-slot="{ errors, processing }" class="space-y-8">
        <FormTabs
            :errors="errors"
            :tabs="[
                { value: 'details' },
                {
                    value: 'registration',
                    fields: [
                        'price',
                        'capacity',
                        'registration_opens_at',
                        'registration_closes_at',
                    ],
                },
                { value: 'seo', prefix: 'seo' },
            ]"
            class="w-full"
        >
            <TabsList>
                <TabsTrigger value="details">Details</TabsTrigger>
                <TabsTrigger value="registration"
                    >Registration & Pricing</TabsTrigger
                >
                <TabsTrigger value="seo">SEO & Sharing</TabsTrigger>
            </TabsList>

            <TabsContent value="details" class="space-y-6 pt-4">
                <div class="grid content-start gap-2">
                    <Label for="name">Camp name</Label>
                    <Input
                        id="name"
                        name="name"
                        :default-value="camp?.name ?? ''"
                        required
                        placeholder="Winter Hitting Camp"
                    />
                    <InputError :message="errors.name" />
                </div>

                <div class="grid content-start gap-2">
                    <Label for="description">Description</Label>
                    <Textarea
                        id="description"
                        name="description"
                        rows="6"
                        :default-value="camp?.description ?? ''"
                        placeholder="What players will work on, who it's for, what to bring…"
                    />
                    <InputError :message="errors.description" />
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid content-start gap-2">
                        <Label for="location">Location</Label>
                        <Input
                            id="location"
                            name="location"
                            :default-value="camp?.location ?? ''"
                            placeholder="Eagles Training Facility"
                        />
                        <InputError :message="errors.location" />
                    </div>
                    <div class="grid content-start gap-2">
                        <Label for="age_range">Age range</Label>
                        <Input
                            id="age_range"
                            name="age_range"
                            :default-value="camp?.age_range ?? ''"
                            placeholder="9U–12U"
                        />
                        <InputError :message="errors.age_range" />
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <DateTimePicker
                        name="starts_at"
                        label="Starts"
                        :model-value="camp?.starts_at"
                        required
                        :error="errors.starts_at"
                    />
                    <DateTimePicker
                        name="ends_at"
                        label="Ends"
                        :model-value="camp?.ends_at"
                        optional
                        :error="errors.ends_at"
                    />
                </div>

                <ImageUpload
                    name="image"
                    label="Camp image"
                    remove-name="remove_image"
                    :current-url="camp?.image_url ?? null"
                    :error="errors.image"
                />

                <div class="grid content-start gap-2">
                    <Label for="youtube_url"
                        >YouTube video URL
                        <span class="text-muted-foreground"
                            >(optional)</span
                        ></Label
                    >
                    <Input
                        id="youtube_url"
                        name="youtube_url"
                        type="url"
                        :default-value="camp?.youtube_url ?? ''"
                        placeholder="https://www.youtube.com/watch?v=…"
                    />
                    <InputError :message="errors.youtube_url" />
                </div>

                <div class="flex items-center gap-3 rounded-md border p-4">
                    <Switch id="is_published" v-model="isPublished" />
                    <input
                        type="hidden"
                        name="is_published"
                        :value="isPublished ? 1 : 0"
                    />
                    <div>
                        <Label for="is_published" class="cursor-pointer"
                            >Published</Label
                        >
                        <p class="text-xs text-muted-foreground">
                            Unpublished camps are hidden from the public site.
                        </p>
                    </div>
                </div>
            </TabsContent>

            <TabsContent value="registration" class="space-y-6 pt-4">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid content-start gap-2">
                        <Label for="price">Price (USD)</Label>
                        <div class="relative">
                            <span
                                class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-sm text-muted-foreground"
                                >$</span
                            >
                            <Input
                                id="price"
                                name="price"
                                type="number"
                                step="0.01"
                                min="0"
                                class="pl-7"
                                :default-value="camp?.price_dollars ?? '0.00'"
                                required
                            />
                        </div>
                        <p class="text-xs text-muted-foreground">
                            Enter 0 for a free camp. Paid camps collect payment
                            through Stripe.
                        </p>
                        <InputError :message="errors.price" />
                    </div>
                    <div class="grid content-start gap-2">
                        <Label for="capacity"
                            >Capacity
                            <span class="text-muted-foreground"
                                >(optional)</span
                            ></Label
                        >
                        <Input
                            id="capacity"
                            name="capacity"
                            type="number"
                            min="1"
                            :default-value="camp?.capacity ?? ''"
                            placeholder="Unlimited"
                        />
                        <InputError :message="errors.capacity" />
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <DateTimePicker
                        name="registration_opens_at"
                        label="Registration opens"
                        :model-value="camp?.registration_opens_at"
                        optional
                        :error="errors.registration_opens_at"
                        hint="Leave blank to open immediately once published."
                    />
                    <DateTimePicker
                        name="registration_closes_at"
                        label="Registration closes"
                        :model-value="camp?.registration_closes_at"
                        optional
                        :error="errors.registration_closes_at"
                        hint="Leave blank to stay open until the camp starts."
                    />
                </div>
            </TabsContent>

            <TabsContent value="seo" class="pt-4">
                <SeoFields
                    :seo="seo"
                    :errors="errors"
                    :fallback="seoFallback"
                />
            </TabsContent>
        </FormTabs>

        <div class="flex items-center gap-3 border-t pt-6">
            <Button :disabled="processing">{{
                camp ? 'Save changes' : 'Create camp'
            }}</Button>
            <slot name="actions" />
        </div>
    </Form>
</template>
