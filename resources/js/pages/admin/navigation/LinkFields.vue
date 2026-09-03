<script setup lang="ts">
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { MenuItem, PageOption } from '@/pages/admin/navigation/types';

/**
 * Shared field set for adding/editing a navigation link. Must be placed
 * inside an Inertia <Form>; submits label, link_type, route_name|url, flags.
 */
const props = withDefaults(
    defineProps<{
        pages: PageOption[];
        errors: Record<string, string>;
        item?: MenuItem | null;
    }>(),
    { item: null },
);

const uid = props.item?.id ?? 'new';
const linkType = ref<'page' | 'custom'>(props.item?.link_type ?? 'page');
const routeName = ref<string>(
    props.item?.route_name ?? props.pages[0]?.value ?? '',
);
const newTab = ref<boolean>(props.item?.opens_in_new_tab ?? false);
const visible = ref<boolean>(props.item?.is_visible ?? true);
</script>

<template>
    <div class="grid content-start gap-2 sm:col-span-4">
        <Label :for="`label-${uid}`">Label</Label>
        <Input
            :id="`label-${uid}`"
            name="label"
            :default-value="item?.label ?? ''"
            maxlength="40"
            required
        />
        <InputError :message="errors.label" />
    </div>

    <div class="grid content-start gap-2 sm:col-span-3">
        <Label :for="`type-${uid}`">Links to</Label>
        <input type="hidden" name="link_type" :value="linkType" />
        <Select v-model="linkType">
            <SelectTrigger :id="`type-${uid}`"><SelectValue /></SelectTrigger>
            <SelectContent>
                <SelectItem value="page">A page on this site</SelectItem>
                <SelectItem value="custom">Custom URL</SelectItem>
            </SelectContent>
        </Select>
    </div>

    <div
        v-if="linkType === 'page'"
        class="grid content-start gap-2 sm:col-span-5"
    >
        <Label :for="`page-${uid}`">Page</Label>
        <input type="hidden" name="route_name" :value="routeName" />
        <Select v-model="routeName">
            <SelectTrigger :id="`page-${uid}`"
                ><SelectValue placeholder="Choose a page"
            /></SelectTrigger>
            <SelectContent>
                <SelectItem
                    v-for="page in pages"
                    :key="page.value"
                    :value="page.value"
                    >{{ page.label }}</SelectItem
                >
            </SelectContent>
        </Select>
        <InputError :message="errors.route_name" />
    </div>
    <div v-else class="grid content-start gap-2 sm:col-span-5">
        <Label :for="`url-${uid}`">URL</Label>
        <Input
            :id="`url-${uid}`"
            name="url"
            type="text"
            :default-value="item?.url ?? ''"
            placeholder="https://… or /path"
        />
        <InputError :message="errors.url" />
    </div>

    <div class="flex flex-wrap items-center gap-6 sm:col-span-12">
        <input type="hidden" name="opens_in_new_tab" :value="newTab ? 1 : 0" />
        <label class="flex items-center gap-2 text-sm">
            <Checkbox
                :model-value="newTab"
                @update:model-value="(v) => (newTab = v === true)"
            />
            Open in a new tab
        </label>
        <input type="hidden" name="is_visible" :value="visible ? 1 : 0" />
        <label class="flex items-center gap-2 text-sm">
            <Checkbox
                :model-value="visible"
                @update:model-value="(v) => (visible = v === true)"
            />
            Visible on the site
        </label>
    </div>
</template>
