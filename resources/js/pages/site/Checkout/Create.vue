<script setup lang="ts">
import { Form, Link } from '@inertiajs/vue3';
import { ChevronLeft, Lock } from '@lucide/vue';
import { ref } from 'vue';
import CheckoutController from '@/actions/App/Http/Controllers/Site/CheckoutController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { money } from '@/lib/format';
import { index as cartIndex } from '@/routes/cart';
import type { CartData, Option } from '@/types/merch';

const props = defineProps<{
    cart: CartData;
    shippingCents: number;
    fulfillmentOptions: Option[];
}>();

const fulfillment = ref<string>(props.fulfillmentOptions[0]?.value ?? 'pickup');
</script>

<template>
    <div class="container-site py-8 md:py-12">
        <Link
            :href="cartIndex()"
            class="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
        >
            <ChevronLeft class="size-4" /> Back to cart
        </Link>

        <h1
            class="mt-4 font-display text-3xl font-bold tracking-wide text-navy uppercase md:text-4xl dark:text-snow"
        >
            Checkout
        </h1>
        <p class="mt-1 text-muted-foreground">
            Tell us who the order is for, then pay securely on Stripe.
        </p>

        <div class="mt-8 grid gap-8 lg:grid-cols-3">
            <Form
                v-bind="CheckoutController.store.form()"
                v-slot="{ errors, processing }"
                class="space-y-8 lg:col-span-2"
            >
                <input
                    type="text"
                    name="website"
                    tabindex="-1"
                    autocomplete="off"
                    class="hidden"
                    aria-hidden="true"
                />

                <section class="space-y-4 rounded-lg border bg-card p-6">
                    <h2
                        class="font-display text-xl font-bold text-navy uppercase dark:text-snow"
                    >
                        Contact
                    </h2>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid content-start gap-2 sm:col-span-2">
                            <Label for="name">Full name</Label>
                            <Input
                                id="name"
                                name="name"
                                required
                                autocomplete="name"
                            />
                            <InputError :message="errors.name" />
                        </div>
                        <div class="grid content-start gap-2">
                            <Label for="email">Email</Label>
                            <Input
                                id="email"
                                name="email"
                                type="email"
                                required
                                autocomplete="email"
                            />
                            <p class="text-xs text-muted-foreground">
                                Your receipt goes here.
                            </p>
                            <InputError :message="errors.email" />
                        </div>
                        <div class="grid content-start gap-2">
                            <Label for="phone"
                                >Phone
                                <span class="text-muted-foreground"
                                    >(optional)</span
                                ></Label
                            >
                            <Input
                                id="phone"
                                name="phone"
                                type="tel"
                                autocomplete="tel"
                            />
                            <InputError :message="errors.phone" />
                        </div>
                    </div>
                </section>

                <section class="space-y-4 rounded-lg border bg-card p-6">
                    <h2
                        class="font-display text-xl font-bold text-navy uppercase dark:text-snow"
                    >
                        Delivery
                    </h2>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <label
                            v-for="option in fulfillmentOptions"
                            :key="option.value"
                            class="flex cursor-pointer items-center gap-3 rounded-md border p-4 transition-colors"
                            :class="
                                fulfillment === option.value
                                    ? 'border-sky bg-accent'
                                    : 'hover:border-sky/60'
                            "
                        >
                            <input
                                v-model="fulfillment"
                                type="radio"
                                name="fulfillment"
                                :value="option.value"
                                class="accent-sky"
                            />
                            <span class="font-medium">{{ option.label }}</span>
                            <span
                                v-if="
                                    option.value === 'shipping' &&
                                    shippingCents > 0
                                "
                                class="ml-auto text-sm text-muted-foreground"
                                >{{ money(shippingCents) }}</span
                            >
                            <span
                                v-else-if="option.value === 'shipping'"
                                class="ml-auto text-sm text-muted-foreground"
                                >Free</span
                            >
                        </label>
                    </div>
                    <InputError :message="errors.fulfillment" />

                    <div
                        v-if="fulfillment === 'shipping'"
                        class="grid gap-4 border-t pt-4 sm:grid-cols-6"
                    >
                        <div class="grid content-start gap-2 sm:col-span-6">
                            <Label for="shipping_address_line1"
                                >Street address</Label
                            >
                            <Input
                                id="shipping_address_line1"
                                name="shipping_address_line1"
                                autocomplete="address-line1"
                            />
                            <InputError
                                :message="errors.shipping_address_line1"
                            />
                        </div>
                        <div class="grid content-start gap-2 sm:col-span-6">
                            <Label for="shipping_address_line2"
                                >Apt, suite, etc.
                                <span class="text-muted-foreground"
                                    >(optional)</span
                                ></Label
                            >
                            <Input
                                id="shipping_address_line2"
                                name="shipping_address_line2"
                                autocomplete="address-line2"
                            />
                        </div>
                        <div class="grid content-start gap-2 sm:col-span-3">
                            <Label for="shipping_city">City</Label>
                            <Input
                                id="shipping_city"
                                name="shipping_city"
                                autocomplete="address-level2"
                            />
                            <InputError :message="errors.shipping_city" />
                        </div>
                        <div class="grid content-start gap-2 sm:col-span-1">
                            <Label for="shipping_state">State</Label>
                            <Input
                                id="shipping_state"
                                name="shipping_state"
                                maxlength="2"
                                placeholder="IL"
                                autocomplete="address-level1"
                            />
                            <InputError :message="errors.shipping_state" />
                        </div>
                        <div class="grid content-start gap-2 sm:col-span-2">
                            <Label for="shipping_postal_code">ZIP</Label>
                            <Input
                                id="shipping_postal_code"
                                name="shipping_postal_code"
                                autocomplete="postal-code"
                            />
                            <InputError
                                :message="errors.shipping_postal_code"
                            />
                        </div>
                    </div>
                    <p v-else class="text-sm text-muted-foreground">
                        We'll email you when your order is ready and where to
                        pick it up.
                    </p>
                </section>

                <InputError :message="errors.cart" />

                <Button
                    size="lg"
                    class="w-full bg-sky font-semibold text-navy hover:bg-sky-dark hover:text-white sm:w-auto"
                    :disabled="processing"
                >
                    <Lock class="size-4" /> Continue to payment
                </Button>
                <p class="text-xs text-muted-foreground">
                    You'll be redirected to Stripe to enter your card details.
                    We never see your card number.
                </p>
            </Form>

            <aside
                class="h-fit rounded-lg border bg-card p-6 lg:sticky lg:top-24"
            >
                <h2
                    class="font-display text-xl font-bold text-navy uppercase dark:text-snow"
                >
                    Order summary
                </h2>
                <ul class="mt-4 divide-y text-sm">
                    <li
                        v-for="line in cart.lines"
                        :key="line.variant_id"
                        class="flex justify-between gap-3 py-2"
                    >
                        <span>
                            <span class="font-medium"
                                >{{ line.quantity }}×</span
                            >
                            {{ line.product_name }}
                            <span class="text-muted-foreground"
                                >({{ line.label }})</span
                            >
                        </span>
                        <span>{{ money(line.line_total) }}</span>
                    </li>
                </ul>
                <dl class="mt-4 space-y-2 border-t pt-4 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-muted-foreground">Subtotal</dt>
                        <dd>{{ money(cart.subtotal) }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-muted-foreground">Shipping</dt>
                        <dd>
                            {{
                                fulfillment === 'shipping' && shippingCents > 0
                                    ? money(shippingCents)
                                    : 'Free'
                            }}
                        </dd>
                    </div>
                    <div
                        class="flex justify-between border-t pt-2 text-base font-semibold"
                    >
                        <dt>Total</dt>
                        <dd>
                            {{
                                money(
                                    cart.subtotal +
                                        (fulfillment === 'shipping'
                                            ? shippingCents
                                            : 0),
                                )
                            }}
                        </dd>
                    </div>
                </dl>
            </aside>
        </div>
    </div>
</template>
