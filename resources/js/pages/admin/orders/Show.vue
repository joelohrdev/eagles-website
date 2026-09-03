<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ExternalLink } from '@lucide/vue';
import OrderController from '@/actions/App/Http/Controllers/Admin/OrderController';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';
import { formatDateTime, money } from '@/lib/format';
import { edit as editCamp } from '@/routes/admin/camps';
import { index, show } from '@/routes/admin/orders';
import type { Option, Order } from '@/types/merch';

const props = defineProps<{
    order: Order;
    stripeUrl: string | null;
    statuses: Option[];
}>();

defineOptions({
    layout: (page: { props: { order: Order } }) => ({
        breadcrumbs: [
            { title: 'Orders', href: index() },
            { title: page.props.order.number, href: show(page.props.order.id) },
        ],
    }),
});

const allowedNext: Record<string, string[]> = {
    pending: ['cancelled'],
    paid: ['fulfilled', 'cancelled', 'refunded'],
    fulfilled: ['paid', 'refunded'],
    cancelled: [],
    refunded: [],
};

const statusOptions = props.statuses.filter(
    (s) =>
        s.value === props.order.status ||
        allowedNext[props.order.status]?.includes(s.value),
);
</script>

<template>
    <Head :title="`Order ${order.number}`" />

    <div class="flex flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <Heading
                    :title="`Order ${order.number}`"
                    :description="`Placed ${formatDateTime(order.created_at)}`"
                />
                <div class="-mt-6 flex items-center gap-2">
                    <StatusBadge :status="order.status" />
                    <span class="text-sm text-muted-foreground capitalize">{{
                        order.type === 'camp'
                            ? 'Camp registration'
                            : 'Merch order'
                    }}</span>
                </div>
            </div>
            <Button v-if="stripeUrl" as-child variant="outline" size="sm">
                <a :href="stripeUrl" target="_blank" rel="noopener"
                    ><ExternalLink class="size-4" /> View payment in Stripe</a
                >
            </Button>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <Card>
                    <CardHeader><CardTitle>Items</CardTitle></CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Item</TableHead>
                                    <TableHead>Options</TableHead>
                                    <TableHead class="text-right"
                                        >Qty</TableHead
                                    >
                                    <TableHead class="text-right"
                                        >Price</TableHead
                                    >
                                    <TableHead class="text-right"
                                        >Total</TableHead
                                    >
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow
                                    v-for="item in order.items"
                                    :key="item.id"
                                >
                                    <TableCell class="font-medium">{{
                                        item.description
                                    }}</TableCell>
                                    <TableCell class="text-muted-foreground">{{
                                        [item.size, item.color]
                                            .filter(Boolean)
                                            .join(' / ') || '—'
                                    }}</TableCell>
                                    <TableCell class="text-right">{{
                                        item.quantity
                                    }}</TableCell>
                                    <TableCell class="text-right">{{
                                        money(item.unit_price)
                                    }}</TableCell>
                                    <TableCell class="text-right">{{
                                        money(item.unit_price * item.quantity)
                                    }}</TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                        <div class="mt-4 flex justify-end">
                            <dl class="w-56 space-y-1 text-sm">
                                <div class="flex justify-between">
                                    <dt class="text-muted-foreground">
                                        Subtotal
                                    </dt>
                                    <dd>{{ money(order.subtotal) }}</dd>
                                </div>
                                <div
                                    class="flex justify-between border-t pt-1 font-semibold"
                                >
                                    <dt>Total</dt>
                                    <dd>{{ money(order.total) }}</dd>
                                </div>
                            </dl>
                        </div>
                    </CardContent>
                </Card>

                <Card v-if="order.camp_registration">
                    <CardHeader
                        ><CardTitle>Camp registration</CardTitle></CardHeader
                    >
                    <CardContent class="grid gap-3 text-sm sm:grid-cols-2">
                        <div>
                            <p class="text-muted-foreground">Camp</p>
                            <Link
                                :href="
                                    editCamp(order.camp_registration.camp.slug)
                                "
                                class="font-medium hover:underline"
                                >{{ order.camp_registration.camp.name }}</Link
                            >
                            <p class="text-xs text-muted-foreground">
                                {{
                                    formatDateTime(
                                        order.camp_registration.camp.starts_at,
                                    )
                                }}
                            </p>
                        </div>
                        <div>
                            <p class="text-muted-foreground">Player</p>
                            <p class="font-medium">
                                {{ order.camp_registration.player_first_name }}
                                {{ order.camp_registration.player_last_name }}
                            </p>
                        </div>
                        <div>
                            <p class="text-muted-foreground">
                                Parent / guardian
                            </p>
                            <p class="font-medium">
                                {{ order.camp_registration.parent_name }}
                            </p>
                            <p class="text-xs text-muted-foreground">
                                {{ order.camp_registration.email }} ·
                                {{ order.camp_registration.phone }}
                            </p>
                        </div>
                        <div>
                            <p class="text-muted-foreground">
                                Registration status
                            </p>
                            <StatusBadge
                                :status="order.camp_registration.status"
                            />
                        </div>
                    </CardContent>
                </Card>
            </div>

            <div class="space-y-6">
                <Card>
                    <CardHeader><CardTitle>Customer</CardTitle></CardHeader>
                    <CardContent class="space-y-1 text-sm">
                        <p class="font-medium">{{ order.name }}</p>
                        <p>
                            <a
                                :href="`mailto:${order.email}`"
                                class="hover:underline"
                                >{{ order.email }}</a
                            >
                        </p>
                        <p v-if="order.phone">
                            <a
                                :href="`tel:${order.phone}`"
                                class="hover:underline"
                                >{{ order.phone }}</a
                            >
                        </p>
                    </CardContent>
                </Card>

                <Card v-if="order.type === 'merch'">
                    <CardHeader><CardTitle>Fulfillment</CardTitle></CardHeader>
                    <CardContent class="space-y-1 text-sm">
                        <p class="font-medium capitalize">
                            {{
                                order.fulfillment === 'shipping'
                                    ? 'Ship to address'
                                    : 'Local pickup'
                            }}
                        </p>
                        <address
                            v-if="order.fulfillment === 'shipping'"
                            class="text-muted-foreground not-italic"
                        >
                            {{ order.shipping_address_line1 }}<br />
                            <template v-if="order.shipping_address_line2"
                                >{{ order.shipping_address_line2 }}<br
                            /></template>
                            {{ order.shipping_city }},
                            {{ order.shipping_state }}
                            {{ order.shipping_postal_code }}
                        </address>
                        <p
                            v-if="order.fulfilled_at"
                            class="text-xs text-muted-foreground"
                        >
                            Fulfilled {{ formatDateTime(order.fulfilled_at) }}
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader><CardTitle>Payment</CardTitle></CardHeader>
                    <CardContent class="space-y-1 text-sm">
                        <p v-if="order.paid_at">
                            Paid {{ formatDateTime(order.paid_at) }}
                        </p>
                        <p v-else class="text-muted-foreground">Not paid</p>
                        <p
                            v-if="order.stripe_payment_intent_id"
                            class="font-mono text-xs break-all text-muted-foreground"
                        >
                            {{ order.stripe_payment_intent_id }}
                        </p>
                        <p class="text-xs text-muted-foreground">
                            Refunds are issued from the Stripe dashboard; mark
                            the order refunded here afterwards.
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader><CardTitle>Update order</CardTitle></CardHeader>
                    <CardContent>
                        <Form
                            v-bind="OrderController.update.form(order.id)"
                            v-slot="{ errors, processing }"
                            class="space-y-4"
                        >
                            <div class="grid content-start gap-2">
                                <Label for="status">Status</Label>
                                <select
                                    id="status"
                                    name="status"
                                    class="h-9 rounded-md border bg-background px-3 text-sm"
                                    :value="order.status"
                                >
                                    <option
                                        v-for="s in statusOptions"
                                        :key="s.value"
                                        :value="s.value"
                                    >
                                        {{ s.label }}
                                    </option>
                                </select>
                                <InputError :message="errors.status" />
                            </div>
                            <div class="grid content-start gap-2">
                                <Label for="notes">Internal notes</Label>
                                <Textarea
                                    id="notes"
                                    name="notes"
                                    rows="3"
                                    :default-value="order.notes ?? ''"
                                    placeholder="Pickup arranged, tracking number, etc."
                                />
                                <InputError :message="errors.notes" />
                            </div>
                            <Button :disabled="processing" class="w-full"
                                >Save</Button
                            >
                        </Form>
                    </CardContent>
                </Card>
            </div>
        </div>
    </div>
</template>
