<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Pencil, Plus } from '@lucide/vue';
import ConfirmDelete from '@/components/admin/ConfirmDelete.vue';
import EmptyState from '@/components/admin/EmptyState.vue';
import Pagination from '@/components/admin/Pagination.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { formatDate } from '@/lib/format';
import { create, destroy, edit, index } from '@/routes/admin/coupons';
import type { Paginated } from '@/types';
import type { AdminCouponRow } from '@/types/coupons';

defineProps<{
    coupons: Paginated<AdminCouponRow>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Coupons', href: index() }],
    },
});
</script>

<template>
    <Head title="Coupons" />

    <div class="flex flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <Heading
                title="Coupons"
                description="Discount codes for camp registrations and merch."
            />
            <Button as-child>
                <Link :href="create()"><Plus class="size-4" /> New coupon</Link>
            </Button>
        </div>

        <EmptyState
            v-if="coupons.data.length === 0"
            title="No coupons yet"
            description="Create a code to give families a discount."
        >
            <Button as-child><Link :href="create()">New coupon</Link></Button>
        </EmptyState>

        <div v-else class="rounded-lg border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Code</TableHead>
                        <TableHead>Discount</TableHead>
                        <TableHead class="hidden md:table-cell"
                            >Works for</TableHead
                        >
                        <TableHead class="hidden lg:table-cell"
                            >Expires</TableHead
                        >
                        <TableHead>Uses</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead class="text-right">Actions</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="coupon in coupons.data" :key="coupon.id">
                        <TableCell>
                            <Link
                                :href="edit(coupon)"
                                class="font-mono font-medium hover:underline"
                                >{{ coupon.code }}</Link
                            >
                            <p
                                v-if="coupon.description"
                                class="text-xs text-muted-foreground"
                            >
                                {{ coupon.description }}
                            </p>
                        </TableCell>
                        <TableCell>{{ coupon.summary }}</TableCell>
                        <TableCell class="hidden md:table-cell">
                            {{ coupon.applies_to_label }}
                            <p
                                v-if="coupon.camp_names.length"
                                class="text-xs text-muted-foreground"
                            >
                                {{ coupon.camp_names.join(', ') }}
                            </p>
                        </TableCell>
                        <TableCell class="hidden lg:table-cell">{{
                            coupon.expires_at
                                ? formatDate(coupon.expires_at)
                                : 'Never'
                        }}</TableCell>
                        <TableCell
                            >{{ coupon.redemptions_count
                            }}<span
                                v-if="coupon.max_redemptions"
                                class="text-muted-foreground"
                            >
                                / {{ coupon.max_redemptions }}</span
                            ></TableCell
                        >
                        <TableCell>
                            <StatusBadge
                                :status="
                                    coupon.is_active ? 'active' : 'inactive'
                                "
                            />
                        </TableCell>
                        <TableCell class="text-right">
                            <div class="flex justify-end gap-1">
                                <Button as-child variant="ghost" size="icon-sm">
                                    <Link :href="edit(coupon)" aria-label="Edit"
                                        ><Pencil class="size-4"
                                    /></Link>
                                </Button>
                                <ConfirmDelete
                                    :href="destroy(coupon)"
                                    size="icon-sm"
                                    title="Delete this coupon?"
                                    :description="`${coupon.code} will stop working. Orders that already used it keep their discount.`"
                                />
                            </div>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <Pagination :paginator="coupons" />
    </div>
</template>
