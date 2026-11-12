<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Search, X } from '@lucide/vue';
import { ref, watch } from 'vue';
import ActivityList from '@/components/admin/activity/ActivityList.vue';
import EmptyState from '@/components/admin/EmptyState.vue';
import Pagination from '@/components/admin/Pagination.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { index } from '@/routes/admin/activity';
import type { Paginated } from '@/types';
import type { ActivityEntry } from '@/types/activity';

const props = defineProps<{
    logs: Paginated<ActivityEntry>;
    filters: {
        category: string;
        q: string;
        registration: number | null;
        order: number | null;
    };
    categories: { value: string; label: string }[];
    /** Set when showing one registration's or order's history. */
    scope: string | null;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Activity log', href: index() }],
    },
});

const q = ref(props.filters.q);
const category = ref(props.filters.category || 'all');

let searchTimer: ReturnType<typeof setTimeout> | undefined;

watch(q, () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(applyFilters, 350);
});

function applyFilters() {
    clearTimeout(searchTimer);
    router.get(
        index().url,
        {
            q: q.value || undefined,
            category: category.value === 'all' ? undefined : category.value,
            registration: props.filters.registration ?? undefined,
            order: props.filters.order ?? undefined,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}
</script>

<template>
    <Head title="Activity log" />

    <div class="flex flex-col gap-6 p-4 md:p-6">
        <Heading
            title="Activity log"
            description="Who did what and when: registrations, payments, cancellations, refunds, and changes to camps, coupons and orders. Kept for two years."
        />

        <div
            v-if="scope"
            class="flex flex-wrap items-center justify-between gap-3 rounded-md border border-sky/40 bg-accent px-4 py-2 text-sm"
        >
            <span class="font-medium">{{ scope }}</span>
            <Button as-child variant="ghost" size="sm">
                <Link :href="index()"
                    ><X class="size-4" /> Show everything</Link
                >
            </Button>
        </div>

        <form
            class="grid gap-3 sm:grid-cols-[1fr_14rem]"
            @submit.prevent="applyFilters"
        >
            <div class="relative">
                <Search
                    class="pointer-events-none absolute top-1/2 left-2.5 size-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    v-model="q"
                    type="search"
                    placeholder="Search player, email, order number, staff member…"
                    class="pl-8"
                />
            </div>
            <Select v-model="category" @update:model-value="applyFilters">
                <SelectTrigger class="w-full"
                    ><SelectValue placeholder="Everything"
                /></SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">Everything</SelectItem>
                    <SelectItem
                        v-for="c in categories"
                        :key="c.value"
                        :value="c.value"
                        >{{ c.label }}</SelectItem
                    >
                </SelectContent>
            </Select>
        </form>

        <EmptyState
            v-if="logs.data.length === 0"
            title="Nothing logged"
            :description="
                filters.q || filters.category || scope
                    ? 'No activity matches these filters.'
                    : 'Activity appears here as people register, pay and make changes.'
            "
        />
        <ActivityList v-else :entries="logs.data" />

        <Pagination :paginator="logs" />
    </div>
</template>
