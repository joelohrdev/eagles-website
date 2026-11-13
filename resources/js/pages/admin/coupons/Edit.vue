<script setup lang="ts">
import { Head, Link, setLayoutProps } from '@inertiajs/vue3';
import ConfirmDelete from '@/components/admin/ConfirmDelete.vue';
import CouponForm from '@/components/admin/coupons/CouponForm.vue';
import Pagination from '@/components/admin/Pagination.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import Heading from '@/components/Heading.vue';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { formatDateTime, money } from '@/lib/format';
import { destroy, edit, index } from '@/routes/admin/coupons';
import { show as showOrder } from '@/routes/admin/orders';
import type { Paginated } from '@/types';
import type {
    AdminCoupon,
    CouponFormOptions,
    CouponUse,
} from '@/types/coupons';

const props = defineProps<
    CouponFormOptions & {
        coupon: AdminCoupon;
        uses: Paginated<CouponUse>;
    }
>();

setLayoutProps({
    breadcrumbs: [
        { title: 'Coupons', href: index() },
        { title: props.coupon.code, href: edit(props.coupon) },
    ],
});
</script>

<template>
    <Head :title="`Edit ${coupon.code}`" />

    <div class="flex flex-col gap-6 p-4 md:p-6">
        <Heading :title="coupon.code" description="Update this coupon code." />
        <CouponForm
            :coupon="coupon"
            :types="types"
            :scopes="scopes"
            :camps="camps"
        >
            <template #actions>
                <ConfirmDelete
                    :href="destroy(coupon)"
                    title="Delete this coupon?"
                    :description="`${coupon.code} will stop working. Orders that already used it keep their discount.`"
                />
            </template>
        </CouponForm>

        <section class="space-y-3 border-t pt-6">
            <Heading
                variant="small"
                title="Used by"
                description="Every order placed with this code, newest first. Cancelled and refunded orders don’t count toward the use limit."
            />
            <p
                v-if="uses.total === 0"
                class="rounded-md border p-4 text-sm text-muted-foreground"
            >
                Nobody has used this code yet.
            </p>
            <div v-else class="overflow-x-auto rounded-md border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Customer</TableHead>
                            <TableHead>For</TableHead>
                            <TableHead>Order</TableHead>
                            <TableHead class="text-right">Discount</TableHead>
                            <TableHead class="text-right">Total</TableHead>
                            <TableHead>Status</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="use in uses.data" :key="use.id">
                            <TableCell>
                                <div class="font-medium">{{ use.name }}</div>
                                <div class="text-xs text-muted-foreground">
                                    {{ use.email }}
                                </div>
                            </TableCell>
                            <TableCell>{{ use.purchase }}</TableCell>
                            <TableCell>
                                <Link
                                    :href="showOrder(use.id)"
                                    class="font-mono text-sm text-sky-dark hover:underline"
                                    >{{ use.number }}</Link
                                >
                                <div class="text-xs text-muted-foreground">
                                    {{ formatDateTime(use.created_at) }}
                                </div>
                            </TableCell>
                            <TableCell class="text-right"
                                >−{{ money(use.discount) }}</TableCell
                            >
                            <TableCell class="text-right font-medium">{{
                                money(use.total)
                            }}</TableCell>
                            <TableCell>
                                <StatusBadge
                                    :status="use.status"
                                    :label="use.status_label"
                                />
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
            <Pagination :paginator="uses" />
        </section>
    </div>
</template>
