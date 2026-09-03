<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { ref } from 'vue';
import CoachController from '@/actions/App/Http/Controllers/Admin/CoachController';
import FormTabs from '@/components/admin/FormTabs.vue';
import ImageUpload from '@/components/admin/ImageUpload.vue';
import SeoFields from '@/components/admin/SeoFields.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Textarea } from '@/components/ui/textarea';
import type { Coach, SeoMetaProp } from '@/types/teams';

const props = withDefaults(
    defineProps<{
        coach?: Coach | null;
        seo?: SeoMetaProp;
    }>(),
    { coach: null, seo: null },
);

const isActive = ref(props.coach?.is_active ?? true);

const formProps = props.coach
    ? CoachController.update.form(props.coach)
    : CoachController.store.form();
</script>

<template>
    <Form v-bind="formProps" class="space-y-8" v-slot="{ errors, processing }">
        <FormTabs
            :errors="errors"
            :tabs="[{ value: 'details' }, { value: 'seo', prefix: 'seo' }]"
            class="w-full"
        >
            <TabsList>
                <TabsTrigger value="details">Details</TabsTrigger>
                <TabsTrigger value="seo">SEO &amp; Sharing</TabsTrigger>
            </TabsList>

            <TabsContent value="details" class="mt-6 space-y-6">
                <div class="grid gap-6 md:grid-cols-2">
                    <div class="grid content-start gap-2">
                        <Label for="name">Name</Label>
                        <Input
                            id="name"
                            name="name"
                            :default-value="coach?.name ?? ''"
                            required
                            placeholder="Coach name"
                        />
                        <InputError :message="errors.name" />
                    </div>
                    <div class="grid content-start gap-2">
                        <Label for="title">Title / role</Label>
                        <Input
                            id="title"
                            name="title"
                            :default-value="coach?.title ?? ''"
                            placeholder="Head Coach, 12U"
                        />
                        <InputError :message="errors.title" />
                    </div>
                    <div class="grid content-start gap-2">
                        <Label for="email">Email</Label>
                        <Input
                            id="email"
                            name="email"
                            type="email"
                            :default-value="coach?.email ?? ''"
                            placeholder="Optional"
                        />
                        <InputError :message="errors.email" />
                    </div>
                    <div class="grid content-start gap-2">
                        <Label for="phone">Phone</Label>
                        <Input
                            id="phone"
                            name="phone"
                            type="tel"
                            :default-value="coach?.phone ?? ''"
                            placeholder="Optional"
                        />
                        <InputError :message="errors.phone" />
                    </div>
                </div>

                <div class="grid content-start gap-2">
                    <Label for="bio">Bio</Label>
                    <Textarea
                        id="bio"
                        name="bio"
                        rows="6"
                        :default-value="coach?.bio ?? ''"
                        placeholder="Playing and coaching background, philosophy, certifications."
                    />
                    <InputError :message="errors.bio" />
                </div>

                <ImageUpload
                    name="photo"
                    label="Headshot"
                    aspect="square"
                    remove-name="remove_photo"
                    :current-url="coach?.photo_url ?? null"
                    :error="errors.photo"
                />

                <div class="flex items-center gap-3">
                    <Switch id="is_active" v-model="isActive" />
                    <input
                        type="hidden"
                        name="is_active"
                        :value="isActive ? 1 : 0"
                    />
                    <Label for="is_active"
                        >Show on the public Coaching Staff page</Label
                    >
                </div>
            </TabsContent>

            <TabsContent value="seo" class="mt-6">
                <SeoFields
                    :seo="seo"
                    :errors="errors"
                    :fallback="{
                        title: coach?.name ?? 'Coach name',
                        description: coach?.bio ?? null,
                        image_url: coach?.photo_url ?? null,
                    }"
                />
            </TabsContent>
        </FormTabs>

        <div class="flex items-center gap-3 border-t pt-6">
            <Button :disabled="processing">{{
                coach ? 'Save changes' : 'Create coach'
            }}</Button>
        </div>
    </Form>
</template>
