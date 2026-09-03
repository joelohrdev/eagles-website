<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import ProductController from '@/actions/App/Http/Controllers/Admin/ProductController';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { create, index } from '@/routes/admin/products';
import ProductForm from './ProductForm.vue';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Products', href: index() },
            { title: 'New product', href: create() },
        ],
    },
});
</script>

<template>
    <Head title="New product" />

    <div class="flex flex-col gap-6 p-4 md:p-6">
        <Heading
            title="New product"
            description="Save the product first, then add sizes and colors."
        />

        <Form
            v-bind="ProductController.store.form()"
            v-slot="{ errors, processing }"
            class="space-y-6"
        >
            <ProductForm :errors="errors" />

            <div class="flex items-center gap-3 border-t pt-6">
                <Button :disabled="processing">Create product</Button>
                <Button as-child variant="ghost"
                    ><Link :href="index()">Cancel</Link></Button
                >
            </div>
        </Form>
    </div>
</template>
