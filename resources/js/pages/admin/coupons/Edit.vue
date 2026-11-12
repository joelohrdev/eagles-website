<script setup lang="ts">
import { Head, setLayoutProps } from '@inertiajs/vue3';
import ConfirmDelete from '@/components/admin/ConfirmDelete.vue';
import CouponForm from '@/components/admin/coupons/CouponForm.vue';
import Heading from '@/components/Heading.vue';
import { destroy, edit, index } from '@/routes/admin/coupons';
import type { AdminCoupon, CouponFormOptions } from '@/types/coupons';

const props = defineProps<
    CouponFormOptions & {
        coupon: AdminCoupon;
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
    </div>
</template>
