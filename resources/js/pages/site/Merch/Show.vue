<script setup lang="ts">
import { Form, Link } from '@inertiajs/vue3';
import { ChevronLeft, ShoppingBag } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import CartController from '@/actions/App/Http/Controllers/Site/CartController';
import InputError from '@/components/InputError.vue';
import ShareButtons from '@/components/site/ShareButtons.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { money } from '@/lib/format';
import { index as cartIndex } from '@/routes/cart';
import { index } from '@/routes/merch';

type Variant = {
    id: number;
    size: string | null;
    color: string | null;
    label: string;
    price: number;
    stock: number | null;
    in_stock: boolean;
    in_cart: number;
};

const props = defineProps<{
    product: {
        id: number;
        name: string;
        slug: string;
        description: string | null;
        price: number;
        image_url: string | null;
        url: string;
    };
    variants: Variant[];
}>();

const sizes = computed(() => [
    ...new Set(
        props.variants.map((v) => v.size).filter((s): s is string => !!s),
    ),
]);
const colors = computed(() => [
    ...new Set(
        props.variants.map((v) => v.color).filter((c): c is string => !!c),
    ),
]);

const selectedSize = ref<string | null>(
    sizes.value.length === 1 ? sizes.value[0] : null,
);
const selectedColor = ref<string | null>(
    colors.value.length === 1 ? colors.value[0] : null,
);
const quantity = ref(1);

const selectedVariant = computed<Variant | null>(() => {
    if (props.variants.length === 0) {
        return null;
    }

    return (
        props.variants.find(
            (v) =>
                (sizes.value.length === 0 || v.size === selectedSize.value) &&
                (colors.value.length === 0 || v.color === selectedColor.value),
        ) ?? null
    );
});

const displayPrice = computed(
    () => selectedVariant.value?.price ?? props.product.price,
);
const needsSelection = computed(
    () =>
        (sizes.value.length > 0 && !selectedSize.value) ||
        (colors.value.length > 0 && !selectedColor.value),
);
const soldOut = computed(
    () => props.variants.length > 0 && props.variants.every((v) => !v.in_stock),
);
const maxQty = computed(() => Math.min(10, selectedVariant.value?.stock ?? 10));

function sizeAvailable(size: string) {
    return props.variants.some(
        (v) =>
            v.size === size &&
            v.in_stock &&
            (colors.value.length === 0 ||
                !selectedColor.value ||
                v.color === selectedColor.value),
    );
}

function colorAvailable(color: string) {
    return props.variants.some(
        (v) =>
            v.color === color &&
            v.in_stock &&
            (sizes.value.length === 0 ||
                !selectedSize.value ||
                v.size === selectedSize.value),
    );
}

watch(maxQty, (max) => {
    if (quantity.value > max) {
        quantity.value = Math.max(1, max);
    }
});
</script>

<template>
    <div class="container-site py-8 md:py-12">
        <Link
            :href="index()"
            class="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
        >
            <ChevronLeft class="size-4" /> Back to merch
        </Link>

        <div class="mt-6 grid gap-8 lg:grid-cols-2 lg:gap-12">
            <div class="overflow-hidden rounded-lg border bg-secondary">
                <img
                    v-if="product.image_url"
                    :src="product.image_url"
                    :alt="product.name"
                    class="aspect-square w-full object-cover"
                    fetchpriority="high"
                />
                <div
                    v-else
                    class="flex aspect-square w-full items-center justify-center font-display text-5xl font-bold text-stone uppercase"
                >
                    Eagles
                </div>
            </div>

            <div>
                <h1
                    class="font-display text-3xl font-bold tracking-wide text-navy uppercase md:text-4xl dark:text-snow"
                >
                    {{ product.name }}
                </h1>
                <p class="mt-2 text-2xl font-semibold text-sky-dark">
                    {{ money(displayPrice) }}
                </p>

                <p
                    v-if="product.description"
                    class="mt-4 whitespace-pre-line text-muted-foreground"
                >
                    {{ product.description }}
                </p>

                <div
                    v-if="variants.length === 0"
                    class="mt-8 rounded-lg border bg-muted/40 p-4 text-sm text-muted-foreground"
                >
                    This item isn't available to order online yet. Check back
                    soon.
                </div>

                <Form
                    v-else
                    v-bind="CartController.store.form()"
                    v-slot="{ errors, processing }"
                    class="mt-8 space-y-6"
                >
                    <input
                        type="hidden"
                        name="product_variant_id"
                        :value="selectedVariant?.id ?? ''"
                    />

                    <fieldset v-if="sizes.length" class="space-y-2">
                        <legend class="text-sm font-medium">Size</legend>
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="size in sizes"
                                :key="size"
                                type="button"
                                class="min-w-12 rounded-md border px-3 py-2 text-sm font-medium transition-colors"
                                :class="[
                                    selectedSize === size
                                        ? 'border-navy bg-navy text-snow'
                                        : 'bg-card hover:border-sky',
                                    !sizeAvailable(size)
                                        ? 'line-through opacity-40'
                                        : '',
                                ]"
                                :aria-pressed="selectedSize === size"
                                @click="
                                    selectedSize =
                                        selectedSize === size ? null : size
                                "
                            >
                                {{ size }}
                            </button>
                        </div>
                    </fieldset>

                    <fieldset v-if="colors.length" class="space-y-2">
                        <legend class="text-sm font-medium">Color</legend>
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="color in colors"
                                :key="color"
                                type="button"
                                class="rounded-md border px-3 py-2 text-sm font-medium transition-colors"
                                :class="[
                                    selectedColor === color
                                        ? 'border-navy bg-navy text-snow'
                                        : 'bg-card hover:border-sky',
                                    !colorAvailable(color)
                                        ? 'line-through opacity-40'
                                        : '',
                                ]"
                                :aria-pressed="selectedColor === color"
                                @click="
                                    selectedColor =
                                        selectedColor === color ? null : color
                                "
                            >
                                {{ color }}
                            </button>
                        </div>
                    </fieldset>

                    <div class="flex flex-wrap items-end gap-4">
                        <div class="grid content-start gap-2">
                            <Label for="quantity">Quantity</Label>
                            <select
                                id="quantity"
                                v-model.number="quantity"
                                name="quantity"
                                class="h-10 w-24 rounded-md border bg-background px-3 text-sm"
                            >
                                <option
                                    v-for="n in Math.max(1, maxQty)"
                                    :key="n"
                                    :value="n"
                                >
                                    {{ n }}
                                </option>
                            </select>
                        </div>
                        <Button
                            size="lg"
                            class="bg-sky font-semibold text-navy hover:bg-sky-dark hover:text-white"
                            :disabled="
                                processing ||
                                soldOut ||
                                needsSelection ||
                                (selectedVariant !== null &&
                                    !selectedVariant.in_stock)
                            "
                        >
                            <ShoppingBag class="size-4" />
                            <template v-if="soldOut">Sold out</template>
                            <template v-else-if="needsSelection"
                                >Select
                                {{
                                    sizes.length && !selectedSize
                                        ? 'a size'
                                        : 'a color'
                                }}</template
                            >
                            <template
                                v-else-if="
                                    selectedVariant && !selectedVariant.in_stock
                                "
                                >Out of stock</template
                            >
                            <template v-else>Add to cart</template>
                        </Button>
                    </div>
                    <p
                        v-if="
                            selectedVariant &&
                            (selectedVariant.in_cart ?? 0) > 0
                        "
                        class="text-sm text-muted-foreground"
                    >
                        You already have {{ selectedVariant.in_cart }} of this
                        size in your
                        <Link :href="cartIndex()" class="underline">cart</Link>
                        — adding more will increase that quantity.
                    </p>
                    <p
                        v-if="
                            selectedVariant?.stock !== null &&
                            selectedVariant &&
                            selectedVariant.stock <= 5 &&
                            selectedVariant.in_stock
                        "
                        class="text-sm text-muted-foreground"
                    >
                        Only {{ selectedVariant.stock }} left.
                    </p>
                    <InputError :message="errors.product_variant_id" />
                    <InputError :message="errors.quantity" />
                </Form>

                <div class="mt-8 border-t pt-6">
                    <ShareButtons
                        :url="product.url"
                        :title="`${product.name} — Eagles Baseball Travel`"
                    />
                </div>
            </div>
        </div>
    </div>
</template>
