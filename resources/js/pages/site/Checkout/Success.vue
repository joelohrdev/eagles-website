<script setup lang="ts">
import { Link, usePoll } from '@inertiajs/vue3';
import { CalendarDays, CheckCircle2, LoaderCircle, MapPin } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { formatDateTime, money } from '@/lib/format';
import { home } from '@/routes';
import { index as campsIndex } from '@/routes/camps';
import { index as merchIndex } from '@/routes/merch';

type Item = {
    id: number;
    description: string;
    size: string | null;
    color: string | null;
    unit_price: number;
    quantity: number;
    line_total: number;
};

const props = defineProps<{
    order: {
        number: string;
        type: 'merch' | 'camp';
        status: string;
        is_paid: boolean;
        is_pending: boolean;
        name: string;
        email: string;
        fulfillment: string;
        fulfillment_label: string;
        shipping: {
            line1: string | null;
            line2: string | null;
            city: string | null;
            state: string | null;
            postal_code: string | null;
        } | null;
        subtotal: number;
        total: number;
        items: Item[];
        camp_registration: {
            player_name: string;
            status: string;
            camp: {
                name: string;
                slug: string;
                starts_at: string;
                ends_at: string | null;
                location: string | null;
                url: string;
            };
        } | null;
    };
}>();

// While Stripe's webhook is in flight the order is still pending — poll until it flips to paid.
const { stop } = usePoll(
    3000,
    { only: ['order'] },
    { autoStart: props.order.is_pending },
);

if (!props.order.is_pending) {
    stop();
}
</script>

<template>
    <div class="container-site max-w-3xl py-12 md:py-16">
        <div class="rounded-lg border bg-card p-6 text-center md:p-10">
            <template v-if="order.is_pending">
                <LoaderCircle class="mx-auto size-12 animate-spin text-sky" />
                <h1
                    class="mt-4 font-display text-3xl font-bold text-navy uppercase dark:text-snow"
                >
                    Confirming your payment…
                </h1>
                <p class="mt-2 text-muted-foreground">
                    Hang tight — this usually takes a few seconds. You can leave
                    this page; we'll email your receipt to
                    <strong>{{ order.email }}</strong> as soon as it's
                    confirmed.
                </p>
            </template>

            <template v-else-if="order.is_paid">
                <CheckCircle2 class="mx-auto size-12 text-sky" />
                <h1
                    class="mt-4 font-display text-3xl font-bold text-navy uppercase dark:text-snow"
                >
                    {{
                        order.type === 'camp'
                            ? "You're registered!"
                            : 'Thank you for your order!'
                    }}
                </h1>
                <p class="mt-2 text-muted-foreground">
                    Order
                    <span class="font-mono font-semibold text-foreground">{{
                        order.number
                    }}</span>
                    · receipt sent to <strong>{{ order.email }}</strong>
                </p>
            </template>

            <template v-else>
                <h1
                    class="font-display text-3xl font-bold text-navy uppercase dark:text-snow"
                >
                    Order {{ order.number }}
                </h1>
                <p class="mt-2 text-muted-foreground">
                    This order is
                    <span class="font-medium capitalize">{{
                        order.status
                    }}</span
                    >. If you think that's a mistake, contact us with your order
                    number.
                </p>
            </template>
        </div>

        <div
            v-if="order.camp_registration"
            class="mt-6 rounded-lg border bg-card p-6"
        >
            <h2
                class="font-display text-xl font-bold text-navy uppercase dark:text-snow"
            >
                {{ order.camp_registration.camp.name }}
            </h2>
            <p class="mt-1 text-sm text-muted-foreground">
                Player:
                <span class="font-medium text-foreground">{{
                    order.camp_registration.player_name
                }}</span>
            </p>
            <ul class="mt-4 space-y-2 text-sm">
                <li class="flex items-center gap-2">
                    <CalendarDays class="size-4 text-sky" />
                    {{ formatDateTime(order.camp_registration.camp.starts_at) }}
                </li>
                <li
                    v-if="order.camp_registration.camp.location"
                    class="flex items-center gap-2"
                >
                    <MapPin class="size-4 text-sky" />
                    {{ order.camp_registration.camp.location }}
                </li>
            </ul>
            <div class="mt-4 flex flex-wrap gap-3">
                <Button as-child variant="outline"
                    ><a :href="order.camp_registration.camp.url"
                        >Camp details</a
                    ></Button
                >
                <Button as-child variant="ghost"
                    ><Link :href="campsIndex()">All camps</Link></Button
                >
            </div>
        </div>

        <div class="mt-6 rounded-lg border bg-card p-6">
            <h2
                class="font-display text-xl font-bold text-navy uppercase dark:text-snow"
            >
                Summary
            </h2>
            <ul class="mt-4 divide-y text-sm">
                <li
                    v-for="item in order.items"
                    :key="item.id"
                    class="flex justify-between gap-3 py-2"
                >
                    <span>
                        <span class="font-medium">{{ item.quantity }}×</span>
                        {{ item.description }}
                        <span
                            v-if="item.size || item.color"
                            class="text-muted-foreground"
                            >({{
                                [item.size, item.color]
                                    .filter(Boolean)
                                    .join(' / ')
                            }})</span
                        >
                    </span>
                    <span>{{ money(item.line_total) }}</span>
                </li>
            </ul>
            <dl class="mt-4 space-y-1 border-t pt-4 text-sm">
                <div class="flex justify-between">
                    <dt class="text-muted-foreground">Subtotal</dt>
                    <dd>{{ money(order.subtotal) }}</dd>
                </div>
                <div class="flex justify-between text-base font-semibold">
                    <dt>Total</dt>
                    <dd>{{ money(order.total) }}</dd>
                </div>
            </dl>

            <div
                v-if="order.type === 'merch'"
                class="mt-4 border-t pt-4 text-sm"
            >
                <p class="font-medium">{{ order.fulfillment_label }}</p>
                <address
                    v-if="order.shipping"
                    class="mt-1 text-muted-foreground not-italic"
                >
                    {{ order.shipping.line1 }}<br />
                    <template v-if="order.shipping.line2"
                        >{{ order.shipping.line2 }}<br
                    /></template>
                    {{ order.shipping.city }}, {{ order.shipping.state }}
                    {{ order.shipping.postal_code }}
                </address>
                <p v-else class="mt-1 text-muted-foreground">
                    We'll email you when your order is ready for pickup.
                </p>
            </div>
        </div>

        <div class="mt-8 flex flex-wrap justify-center gap-3">
            <Button
                as-child
                class="bg-sky font-semibold text-navy hover:bg-sky-dark hover:text-white"
            >
                <Link
                    :href="order.type === 'camp' ? campsIndex() : merchIndex()"
                    >{{
                        order.type === 'camp'
                            ? 'Browse more camps'
                            : 'Continue shopping'
                    }}</Link
                >
            </Button>
            <Button as-child variant="ghost"
                ><Link :href="home()">Back to home</Link></Button
            >
        </div>
    </div>
</template>
