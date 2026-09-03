<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ExternalLink, Plus } from '@lucide/vue';
import { ref } from 'vue';
import ProductController from '@/actions/App/Http/Controllers/Admin/ProductController';
import ProductVariantController from '@/actions/App/Http/Controllers/Admin/ProductVariantController';
import ConfirmDelete from '@/components/admin/ConfirmDelete.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { edit, index } from '@/routes/admin/products';
import { destroy as destroyVariant } from '@/routes/admin/products/variants';
import type { Product, ProductSeo, ProductVariant } from '@/types/merch';
import ProductForm from './ProductForm.vue';

const props = defineProps<{
    product: Product;
    seo: ProductSeo | null;
    publicUrl: string;
}>();

defineOptions({
    layout: (page: { props: { product: Product } }) => ({
        breadcrumbs: [
            { title: 'Products', href: index() },
            {
                title: page.props.product.name,
                href: edit(page.props.product.slug),
            },
        ],
    }),
});

const variantActive = ref<Record<number, boolean>>(
    Object.fromEntries(
        (props.product.variants ?? []).map((v) => [v.id, v.is_active]),
    ),
);
const newVariantActive = ref(true);

const dollars = (cents: number | null) =>
    cents === null ? '' : (cents / 100).toFixed(2);
const variantKey = (variant: ProductVariant) => ({
    product: props.product.slug,
    variant: variant.id,
});
</script>

<template>
    <Head :title="`Edit ${product.name}`" />

    <div class="flex flex-col gap-10 p-4 md:p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <Heading
                :title="product.name"
                description="Update details, image, and search/sharing settings."
            />
            <Button as-child variant="outline" size="sm">
                <a :href="publicUrl" target="_blank" rel="noopener"
                    ><ExternalLink class="size-4" /> View in store</a
                >
            </Button>
        </div>

        <Form
            v-bind="ProductController.update.form(product.slug)"
            v-slot="{ errors, processing }"
            class="space-y-6"
        >
            <ProductForm
                :product="product"
                :seo="seo"
                :errors="errors"
                :public-url="publicUrl"
            />

            <div class="flex items-center gap-3 border-t pt-6">
                <Button :disabled="processing">Save changes</Button>
                <Button as-child variant="ghost"
                    ><Link :href="index()">Back to products</Link></Button
                >
            </div>
        </Form>

        <section class="space-y-4">
            <Heading
                variant="small"
                title="Sizes & colors (variants)"
                description="Each size/color combination customers can order. Leave stock blank for unlimited. Price override is optional."
            />

            <div class="overflow-x-auto rounded-lg border">
                <div class="min-w-[56rem]">
                    <div
                        class="grid grid-cols-[1fr_1fr_1fr_6rem_8rem_5rem_10rem] gap-3 border-b bg-muted/40 px-4 py-2 text-xs font-medium tracking-wide text-muted-foreground uppercase"
                    >
                        <span>Size</span><span>Color</span><span>SKU</span
                        ><span>Stock</span><span>Price override</span
                        ><span>Active</span
                        ><span class="text-right">Actions</span>
                    </div>
                    <p
                        v-if="!product.variants?.length"
                        class="px-4 py-8 text-center text-sm text-muted-foreground"
                    >
                        No variants yet — add at least one so the product can be
                        purchased.
                    </p>
                    <Form
                        v-for="variant in product.variants"
                        :key="variant.id"
                        v-bind="
                            ProductVariantController.update.form(
                                variantKey(variant),
                            )
                        "
                        v-slot="{ errors: verrors, processing: vprocessing }"
                        class="grid grid-cols-[1fr_1fr_1fr_6rem_8rem_5rem_10rem] items-start gap-3 border-b px-4 py-3 last:border-b-0"
                    >
                        <div>
                            <Input
                                name="size"
                                :default-value="variant.size ?? ''"
                                placeholder="M"
                                class="h-8"
                                aria-label="Size"
                            />
                            <InputError :message="verrors.size" />
                        </div>
                        <div>
                            <Input
                                name="color"
                                :default-value="variant.color ?? ''"
                                placeholder="Navy"
                                class="h-8"
                                aria-label="Color"
                            />
                            <InputError :message="verrors.color" />
                        </div>
                        <div>
                            <Input
                                name="sku"
                                :default-value="variant.sku ?? ''"
                                class="h-8"
                                aria-label="SKU"
                            />
                        </div>
                        <div>
                            <Input
                                name="stock"
                                type="number"
                                min="0"
                                :default-value="variant.stock ?? ''"
                                placeholder="∞"
                                class="h-8"
                                aria-label="Stock"
                            />
                            <InputError :message="verrors.stock" />
                        </div>
                        <div>
                            <Input
                                name="price_override"
                                type="number"
                                step="0.01"
                                min="0"
                                :default-value="dollars(variant.price_override)"
                                placeholder="—"
                                class="h-8"
                                aria-label="Price override"
                            />
                            <InputError :message="verrors.price_override" />
                        </div>
                        <div class="pt-1.5">
                            <Switch
                                v-model="variantActive[variant.id]"
                                :aria-label="`Variant ${variant.size ?? ''} ${variant.color ?? ''} active`"
                            />
                            <input
                                type="hidden"
                                name="is_active"
                                :value="variantActive[variant.id] ? 1 : 0"
                            />
                        </div>
                        <div class="flex items-center justify-end gap-1">
                            <Button
                                size="sm"
                                variant="outline"
                                :disabled="vprocessing"
                                >Save</Button
                            >
                            <ConfirmDelete
                                :href="destroyVariant(variantKey(variant))"
                                size="icon-sm"
                                title="Remove this variant?"
                                description="Customers will no longer be able to pick this size/color."
                            />
                        </div>
                    </Form>
                </div>
            </div>

            <Form
                v-bind="ProductVariantController.store.form(product.slug)"
                v-slot="{ errors: nerrors, processing: nprocessing }"
                :reset-on-success="[
                    'size',
                    'color',
                    'sku',
                    'stock',
                    'price_override',
                ]"
                class="grid items-end gap-3 rounded-lg border bg-muted/30 p-4 sm:grid-cols-2 lg:grid-cols-7"
            >
                <div class="grid gap-1">
                    <Label for="new_size">Size</Label>
                    <Input id="new_size" name="size" placeholder="M" />
                    <InputError :message="nerrors.size" />
                </div>
                <div class="grid gap-1">
                    <Label for="new_color">Color</Label>
                    <Input id="new_color" name="color" placeholder="Navy" />
                    <InputError :message="nerrors.color" />
                </div>
                <div class="grid gap-1">
                    <Label for="new_sku">SKU</Label>
                    <Input id="new_sku" name="sku" />
                </div>
                <div class="grid gap-1">
                    <Label for="new_stock">Stock</Label>
                    <Input
                        id="new_stock"
                        name="stock"
                        type="number"
                        min="0"
                        placeholder="∞"
                    />
                    <InputError :message="nerrors.stock" />
                </div>
                <div class="grid gap-1">
                    <Label for="new_price_override">Price override</Label>
                    <Input
                        id="new_price_override"
                        name="price_override"
                        type="number"
                        step="0.01"
                        min="0"
                        placeholder="—"
                    />
                    <InputError :message="nerrors.price_override" />
                </div>
                <div class="flex items-center gap-2 pb-2">
                    <Switch id="new_is_active" v-model="newVariantActive" />
                    <input
                        type="hidden"
                        name="is_active"
                        :value="newVariantActive ? 1 : 0"
                    />
                    <Label for="new_is_active">Active</Label>
                </div>
                <div>
                    <Button :disabled="nprocessing" class="w-full"
                        ><Plus class="size-4" /> Add variant</Button
                    >
                </div>
            </Form>
        </section>
    </div>
</template>
