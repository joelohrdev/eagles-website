<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import PageHero from '@/components/site/PageHero.vue';
import { Button } from '@/components/ui/button';
import { money } from '@/lib/format';
import { contact } from '@/routes';
import { show } from '@/routes/merch';

type ProductCard = {
    id: number;
    name: string;
    slug: string;
    price: number;
    image_url: string | null;
    image_thumbnail_url: string | null;
    has_variants: boolean;
    in_stock: boolean;
};

defineProps<{
    products: ProductCard[];
}>();
</script>

<template>
    <div>
        <PageHero
            eyebrow="Official gear"
            title="Eagles Merch"
            description="Rep the Eagles on and off the field. Order online and pick up locally — every purchase supports our players."
        />

        <section class="container-site py-12 md:py-16">
            <div
                v-if="products.length === 0"
                class="rounded-lg border border-dashed px-6 py-16 text-center"
            >
                <h2
                    class="font-display text-2xl font-bold text-navy uppercase dark:text-snow"
                >
                    Store opening soon
                </h2>
                <p class="mx-auto mt-2 max-w-md text-muted-foreground">
                    We're stocking the shelves. Check back soon, or reach out if
                    you're looking for something specific.
                </p>
                <Button
                    as-child
                    class="mt-6 bg-sky font-semibold text-navy hover:bg-sky-dark hover:text-white"
                >
                    <Link :href="contact()">Contact us</Link>
                </Button>
            </div>

            <div
                v-else
                class="grid grid-cols-2 gap-4 sm:gap-6 md:grid-cols-3 lg:grid-cols-4"
            >
                <Link
                    v-for="product in products"
                    :key="product.id"
                    :href="show(product.slug)"
                    class="group flex flex-col overflow-hidden rounded-lg border bg-card shadow-sm transition-shadow hover:shadow-md"
                    prefetch
                >
                    <div
                        class="relative aspect-square w-full overflow-hidden bg-secondary"
                    >
                        <img
                            v-if="product.image_thumbnail_url"
                            :src="product.image_thumbnail_url"
                            :alt="product.name"
                            loading="lazy"
                            class="size-full object-cover transition-transform duration-300 group-hover:scale-105"
                        />
                        <div
                            v-else
                            class="flex size-full items-center justify-center font-display text-3xl font-bold text-stone uppercase"
                        >
                            Eagles
                        </div>
                        <span
                            v-if="!product.in_stock || !product.has_variants"
                            class="absolute top-2 left-2 rounded bg-navy/90 px-2 py-0.5 text-xs font-semibold text-snow"
                        >
                            {{
                                product.has_variants
                                    ? 'Sold out'
                                    : 'Coming soon'
                            }}
                        </span>
                    </div>
                    <div class="flex flex-1 flex-col p-3 sm:p-4">
                        <h2
                            class="leading-snug font-medium text-navy dark:text-snow"
                        >
                            {{ product.name }}
                        </h2>
                        <p class="mt-auto pt-2 font-semibold text-sky-dark">
                            {{ money(product.price) }}
                        </p>
                    </div>
                </Link>
            </div>
        </section>
    </div>
</template>
