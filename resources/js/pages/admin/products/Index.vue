<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Pencil, Plus } from '@lucide/vue';
import ConfirmDelete from '@/components/admin/ConfirmDelete.vue';
import DragHandle from '@/components/admin/DragHandle.vue';
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
import { useSortableList } from '@/composables/useSortableList';
import { money } from '@/lib/format';
import { create, destroy, edit, index, reorder } from '@/routes/admin/products';
import type { Paginated } from '@/types';
import type { Product } from '@/types/merch';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Products', href: index() }],
    },
});

const props = defineProps<{
    products: Paginated<Product>;
}>();

const {
    container,
    items: orderedProducts,
    move,
} = useSortableList<Product>(() => props.products.data, reorder.url());
</script>

<template>
    <Head title="Products" />

    <div class="flex flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <Heading
                title="Products"
                description="Merch sold in the online store. Add sizes and colors on each product, and drag a row by its handle to reorder them."
            />
            <Button as-child>
                <Link :href="create()"
                    ><Plus class="size-4" /> New product</Link
                >
            </Button>
        </div>

        <EmptyState
            v-if="products.data.length === 0"
            title="No products yet"
            description="Create your first product, then add sizes and colors so customers can order."
        >
            <Button as-child
                ><Link :href="create()">Create product</Link></Button
            >
        </EmptyState>

        <div v-else class="rounded-lg border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead class="w-10"></TableHead>
                        <TableHead class="w-16"></TableHead>
                        <TableHead>Product</TableHead>
                        <TableHead>Price</TableHead>
                        <TableHead>Variants</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead class="w-32 text-right">Actions</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody ref="container">
                    <TableRow
                        v-for="(product, position) in orderedProducts"
                        :key="product.id"
                    >
                        <TableCell>
                            <DragHandle
                                :label="product.name"
                                @move="move(position, $event)"
                            />
                        </TableCell>
                        <TableCell>
                            <div
                                class="size-12 overflow-hidden rounded-md bg-muted"
                            >
                                <img
                                    v-if="product.image_thumbnail_url"
                                    :src="product.image_thumbnail_url"
                                    alt=""
                                    class="size-full object-cover"
                                />
                            </div>
                        </TableCell>
                        <TableCell>
                            <Link
                                :href="edit(product.slug)"
                                class="font-medium hover:underline"
                                >{{ product.name }}</Link
                            >
                        </TableCell>
                        <TableCell>{{ money(product.price) }}</TableCell>
                        <TableCell>{{ product.variants_count ?? 0 }}</TableCell>
                        <TableCell>
                            <StatusBadge
                                :status="
                                    product.is_active ? 'active' : 'inactive'
                                "
                            />
                        </TableCell>
                        <TableCell class="text-right">
                            <div class="flex items-center justify-end gap-1">
                                <Button as-child variant="ghost" size="icon-sm">
                                    <Link
                                        :href="edit(product.slug)"
                                        aria-label="Edit"
                                        ><Pencil class="size-4"
                                    /></Link>
                                </Button>
                                <ConfirmDelete
                                    :href="destroy(product.slug)"
                                    size="icon-sm"
                                    title="Delete this product?"
                                    description="The product, its variants, and image will be removed. Past orders keep their line items."
                                />
                            </div>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <Pagination :paginator="products" />
    </div>
</template>
