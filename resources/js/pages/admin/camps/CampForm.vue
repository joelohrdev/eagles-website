<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import CampController from '@/actions/App/Http/Controllers/Admin/CampController';
import CampCoachesFields from '@/components/admin/camps/CampCoachesFields.vue';
import CampScheduleFields from '@/components/admin/camps/CampScheduleFields.vue';
import FormTabs from '@/components/admin/FormTabs.vue';
import ImageUpload from '@/components/admin/ImageUpload.vue';
import SeoFields from '@/components/admin/SeoFields.vue';
import DateTimePicker from '@/components/DateTimePicker.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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

/** Whether the camp is live now; the save buttons decide what it becomes. */
/** Bumped after every successful save to reload the schedule and coaches with saved ids. */
const scheduleVersion = ref(0);

const isPublished = computed(() => props.camp?.is_published ?? false);

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
    <Form
        v-bind="formProps"
        v-slot="{ errors, processing }"
        class="space-y-8"
        @success="scheduleVersion++"
    >
        <FormTabs
            :errors="errors"
            :tabs="[
                { value: 'details' },
                {
                    value: 'schedule',
                    fields: ['bundle_price', 'capacity'],
                    prefix: ['sessions', 'age_groups'],
                },
                { value: 'coaches', prefix: 'coaches' },
                {
                    value: 'registration',
                    fields: ['registration_opens_at', 'registration_closes_at'],
                },
                { value: 'seo', prefix: 'seo' },
            ]"
            class="w-full"
        >
            <TabsList>
                <TabsTrigger value="details">Details</TabsTrigger>
                <TabsTrigger value="schedule">Sessions & Schedule</TabsTrigger>
                <TabsTrigger value="coaches">Coaches</TabsTrigger>
                <TabsTrigger value="registration">Registration</TabsTrigger>
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

                <div class="grid content-start gap-2">
                    <Label for="address"
                        >Address
                        <span class="text-muted-foreground"
                            >(optional)</span
                        ></Label
                    >
                    <Input
                        id="address"
                        name="address"
                        :default-value="camp?.address ?? ''"
                        placeholder="1234 Main St, Naperville, IL 60540"
                        autocomplete="off"
                    />
                    <p class="text-xs text-muted-foreground">
                        Shown on the camp page with a map link, and in families’
                        confirmation emails.
                    </p>
                    <InputError :message="errors.address" />
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
            </TabsContent>

            <TabsContent value="schedule" class="pt-4">
                <!--
                    Remount after each successful save so rows added before
                    the save pick up their new ids. Without that, saving
                    twice would send them as new again and duplicate them.
                -->
                <CampScheduleFields
                    :key="scheduleVersion"
                    :schedule="camp?.schedule"
                    :bundle-price="camp?.bundle_price_dollars"
                    :capacity="camp?.capacity"
                    :errors="errors"
                />
            </TabsContent>

            <TabsContent value="coaches" class="pt-4">
                <CampCoachesFields
                    :key="scheduleVersion"
                    :coaches="camp?.coaches"
                    :errors="errors"
                />
            </TabsContent>

            <TabsContent value="registration" class="space-y-6 pt-4">
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

        <!--
            The clicked button sends is_published. Drafts save with whatever is
            filled in; publishing (or saving a live camp) needs the full schedule.
            The first button is also what Enter submits.
        -->
        <div class="flex flex-wrap items-center gap-3 border-t pt-6">
            <template v-if="isPublished">
                <Button
                    type="submit"
                    name="is_published"
                    value="1"
                    :disabled="processing"
                    >Save changes</Button
                >
                <Button
                    type="submit"
                    name="is_published"
                    value="0"
                    variant="outline"
                    :disabled="processing"
                    >Move to drafts</Button
                >
            </template>
            <template v-else>
                <Button
                    type="submit"
                    name="is_published"
                    value="0"
                    variant="outline"
                    :disabled="processing"
                    >Save draft</Button
                >
                <Button
                    type="submit"
                    name="is_published"
                    value="1"
                    :disabled="processing"
                    >Publish</Button
                >
            </template>
            <p class="text-xs text-muted-foreground">
                {{
                    isPublished
                        ? 'Live on the public site.'
                        : 'Drafts are hidden from the public site and can be saved with details still missing.'
                }}
            </p>
            <slot name="actions" />
        </div>
    </Form>
</template>
