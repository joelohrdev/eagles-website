<script setup lang="ts">
import { Form, Link } from '@inertiajs/vue3';
import { ArrowRight, ShoppingBag, Trash2 } from '@lucide/vue';
import CartController from '@/actions/App/Http/Controllers/Site/CartController';
import PageHero from '@/components/site/PageHero.vue';
import { Button } from '@/components/ui/button';
import { money } from '@/lib/format';
import { create as checkoutCreate } from '@/routes/checkout';
import { index as merchIndex, show } from '@/routes/merch';
import type { CartData } from '@/types/merch';

defineProps<{
    cart: CartData;
}>();
</script>

<template>
    <div>
        <PageHero
            title="Your Cart"
            :description="
                cart.count
                    ? `${cart.count} item${cart.count === 1 ? '' : 's'}`
                    : null
            "
        />

        <section class="container-site py-12 md:py-16">
            <div
                v-if="cart.lines.length === 0"
                class="rounded-lg border border-dashed px-6 py-16 text-center"
            >
                <ShoppingBag class="mx-auto size-10 text-stone" />
                <h2
                    class="mt-3 font-display text-2xl font-bold text-navy uppercase dark:text-snow"
                >
                    Your cart is empty
                </h2>
                <p class="mt-1 text-muted-foreground">
                    Find something you like in the store.
                </p>
                <Button
                    as-child
                    class="mt-6 bg-sky font-semibold text-navy hover:bg-sky-dark hover:text-white"
                >
                    <Link :href="merchIndex()">Shop merch</Link>
                </Button>
            </div>

            <div v-else class="grid gap-8 lg:grid-cols-3">
                <ul class="space-y-4 lg:col-span-2">
                    <li
                        v-for="line in cart.lines"
                        :key="line.variant_id"
                        class="flex gap-4 rounded-lg border bg-card p-4"
                    >
                        <Link
                            :href="show(line.product_slug)"
                            class="size-24 shrink-0 overflow-hidden rounded-md bg-secondary"
                        >
                            <img
                                v-if="line.image_thumbnail_url"
                                :src="line.image_thumbnail_url"
                                :alt="line.product_name"
                                class="size-full object-cover"
                            />
                        </Link>
                        <div
                            class="flex flex-1 flex-col gap-2 sm:flex-row sm:items-start sm:justify-between"
                        >
                            <div>
                                <Link
                                    :href="show(line.product_slug)"
                                    class="font-medium hover:underline"
                                    >{{ line.product_name }}</Link
                                >
                                <p class="text-sm text-muted-foreground">
                                    {{ line.label }}
                                </p>
                                <p class="mt-1 text-sm">
                                    {{ money(line.unit_price) }} each
                                </p>
                            </div>
                            <div class="flex items-center gap-3">
                                <Form
                                    v-bind="
                                        CartController.update.form(
                                            line.variant_id,
                                        )
                                    "
                                    :options="{ preserveScroll: true }"
                                    class="flex items-center gap-2"
                                >
                                    <label
                                        :for="`qty-${line.variant_id}`"
                                        class="sr-only"
                                        >Quantity</label
                                    >
                                    <select
                                        :id="`qty-${line.variant_id}`"
                                        name="quantity"
                                        class="h-9 rounded-md border bg-background px-2 text-sm"
                                        :value="line.quantity"
                                        @change="
                                            (
                                                $event.target as HTMLSelectElement
                                            ).form?.requestSubmit()
                                        "
                                    >
                                        <option
                                            v-for="n in Math.min(
                                                10,
                                                line.stock ?? 10,
                                            )"
                                            :key="n"
                                            :value="n"
                                        >
                                            {{ n }}
                                        </option>
                                    </select>
                                </Form>
                                <span class="w-20 text-right font-semibold">{{
                                    money(line.line_total)
                                }}</span>
                                <Link
                                    :href="
                                        CartController.destroy(line.variant_id)
                                    "
                                    as="button"
                                    preserve-scroll
                                    class="rounded-md p-2 text-muted-foreground hover:bg-secondary hover:text-destructive"
                                    aria-label="Remove item"
                                >
                                    <Trash2 class="size-4" />
                                </Link>
                            </div>
                        </div>
                    </li>
                </ul>

                <aside
                    class="h-fit rounded-lg border bg-card p-6 lg:sticky lg:top-24"
                >
                    <h2
                        class="font-display text-xl font-bold text-navy uppercase dark:text-snow"
                    >
                        Summary
                    </h2>
                    <dl class="mt-4 space-y-2 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-muted-foreground">Subtotal</dt>
                            <dd>{{ money(cart.subtotal) }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-muted-foreground">Shipping</dt>
                            <dd>Free / local pickup</dd>
                        </div>
                        <div
                            class="flex justify-between border-t pt-2 text-base font-semibold"
                        >
                            <dt>Total</dt>
                            <dd>{{ money(cart.subtotal) }}</dd>
                        </div>
                    </dl>
                    <Button
                        as-child
                        size="lg"
                        class="mt-6 w-full bg-sky font-semibold text-navy hover:bg-sky-dark hover:text-white"
                    >
                        <Link :href="checkoutCreate()"
                            >Checkout <ArrowRight class="size-4"
                        /></Link>
                    </Button>
                    <p class="mt-3 text-center text-xs text-muted-foreground">
                        Secure payment by Stripe.
                    </p>
                    <Link
                        :href="merchIndex()"
                        class="mt-4 block text-center text-sm text-muted-foreground hover:text-foreground"
                        >Continue shopping</Link
                    >
                </aside>
            </div>
        </section>
    </div>
</template>
