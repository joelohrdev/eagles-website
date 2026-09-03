<script setup lang="ts">
import { ref } from 'vue';
import FormTabs from '@/components/admin/FormTabs.vue';
import ImageUpload from '@/components/admin/ImageUpload.vue';
import SeoFields from '@/components/admin/SeoFields.vue';
import InputError from '@/components/InputError.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Textarea } from '@/components/ui/textarea';
import type { Product, ProductSeo } from '@/types/merch';

const props = withDefaults(
    defineProps<{
        product?: Product | null;
        seo?: ProductSeo | null;
        errors: Record<string, string>;
        publicUrl?: string;
    }>(),
    { product: null, seo: null, publicUrl: undefined },
);

const isActive = ref(props.product?.is_active ?? true);
const priceDollars = props.product
    ? (props.product.price / 100).toFixed(2)
    : '';
</script>

<template>
    <FormTabs
        :errors="errors"
        :tabs="[{ value: 'details' }, { value: 'seo', prefix: 'seo' }]"
        class="w-full"
    >
        <TabsList>
            <TabsTrigger value="details">Details</TabsTrigger>
            <TabsTrigger value="seo">SEO &amp; Sharing</TabsTrigger>
        </TabsList>

        <TabsContent value="details" class="mt-6 grid gap-6 lg:grid-cols-3">
            <div class="space-y-5 lg:col-span-2">
                <div class="grid content-start gap-2">
                    <Label for="name">Name</Label>
                    <Input
                        id="name"
                        name="name"
                        :default-value="product?.name ?? ''"
                        required
                        maxlength="255"
                        placeholder="Eagles Dri-Fit Tee"
                    />
                    <InputError :message="errors.name" />
                </div>

                <div class="grid content-start gap-2">
                    <Label for="description">Description</Label>
                    <Textarea
                        id="description"
                        name="description"
                        rows="5"
                        :default-value="product?.description ?? ''"
                        placeholder="Fabric, fit, and anything a buyer should know."
                    />
                    <InputError :message="errors.description" />
                </div>

                <div class="grid content-start gap-2 sm:max-w-xs">
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
                            :default-value="priceDollars"
                            required
                            placeholder="25.00"
                        />
                    </div>
                    <InputError :message="errors.price" />
                </div>

                <div class="flex items-center gap-3">
                    <Switch id="is_active" v-model="isActive" />
                    <input
                        type="hidden"
                        name="is_active"
                        :value="isActive ? 1 : 0"
                    />
                    <Label for="is_active">Active (visible in the store)</Label>
                    <InputError :message="errors.is_active" />
                </div>
            </div>

            <div class="space-y-4">
                <ImageUpload
                    name="image"
                    label="Product image"
                    aspect="square"
                    remove-name="remove_image"
                    :current-url="product?.image_url ?? null"
                    :error="errors.image"
                />
            </div>
        </TabsContent>

        <TabsContent value="seo" class="mt-6">
            <SeoFields
                :seo="seo"
                :errors="errors"
                :fallback="{
                    title: product?.name ?? 'Product name',
                    description:
                        product?.description ??
                        'Official Eagles Baseball Travel merch.',
                    image_url: product?.image_url ?? null,
                    url: publicUrl,
                }"
            />
        </TabsContent>
    </FormTabs>
</template>
