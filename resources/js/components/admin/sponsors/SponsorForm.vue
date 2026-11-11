<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { ref } from 'vue';
import SponsorController from '@/actions/App/Http/Controllers/Admin/SponsorController';
import ImageUpload from '@/components/admin/ImageUpload.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import type { Sponsor } from '@/types/sponsors';

const props = withDefaults(
    defineProps<{
        sponsor?: Sponsor | null;
    }>(),
    { sponsor: null },
);

const isActive = ref(props.sponsor?.is_active ?? true);

const formProps = props.sponsor
    ? SponsorController.update.form(props.sponsor)
    : SponsorController.store.form();
</script>

<template>
    <Form v-bind="formProps" class="space-y-8" v-slot="{ errors, processing }">
        <div class="space-y-6">
            <div class="grid gap-6 md:grid-cols-2">
                <div class="grid content-start gap-2">
                    <Label for="name">Sponsor name</Label>
                    <Input
                        id="name"
                        name="name"
                        :default-value="sponsor?.name ?? ''"
                        required
                        placeholder="Smith Family Dental"
                    />
                    <p class="text-xs text-muted-foreground">
                        Used as the logo's alt text for screen readers.
                    </p>
                    <InputError :message="errors.name" />
                </div>
                <div class="grid content-start gap-2">
                    <Label for="website_url">Website</Label>
                    <Input
                        id="website_url"
                        name="website_url"
                        type="url"
                        :default-value="sponsor?.website_url ?? ''"
                        placeholder="https://example.com"
                    />
                    <p class="text-xs text-muted-foreground">
                        Optional. The logo links here in a new tab.
                    </p>
                    <InputError :message="errors.website_url" />
                </div>
            </div>

            <ImageUpload
                name="logo"
                label="Logo"
                aspect="logo"
                hint="PNG with a transparent background works best. JPG, PNG, or WebP up to 5 MB."
                :current-url="sponsor?.logo_url ?? null"
                :error="errors.logo"
            />

            <div class="flex items-center gap-3">
                <Switch id="is_active" v-model="isActive" />
                <input
                    type="hidden"
                    name="is_active"
                    :value="isActive ? 1 : 0"
                />
                <Label for="is_active">Show on the homepage</Label>
            </div>
        </div>

        <div class="flex items-center gap-3 border-t pt-6">
            <Button :disabled="processing">{{
                sponsor ? 'Save changes' : 'Add sponsor'
            }}</Button>
        </div>
    </Form>
</template>
