<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import CouponForm from '@/components/admin/coupons/CouponForm.vue';
import Heading from '@/components/Heading.vue';
import { create, index } from '@/routes/admin/coupons';
import type { CouponFormOptions } from '@/types/coupons';

defineProps<CouponFormOptions>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Coupons', href: index() },
            { title: 'New coupon', href: create() },
        ],
    },
});
</script>

<template>
    <Head title="New coupon" />

    <div class="flex flex-col gap-6 p-4 md:p-6">
        <Heading
            title="New coupon"
            description="A code customers enter for a discount on camps, merch, or both."
        />
        <CouponForm :types="types" :scopes="scopes" :camps="camps" />
    </div>
</template>
