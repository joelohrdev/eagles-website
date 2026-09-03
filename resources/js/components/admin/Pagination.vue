<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import type { Paginated } from '@/types';

defineProps<{
    paginator: Paginated<unknown>;
}>();
</script>

<template>
    <div
        v-if="paginator.last_page > 1"
        class="flex items-center justify-between gap-4 border-t pt-4 text-sm text-muted-foreground"
    >
        <span>
            Showing {{ paginator.from ?? 0 }}–{{ paginator.to ?? 0 }} of
            {{ paginator.total }}
        </span>
        <div class="flex items-center gap-1">
            <Button
                as-child
                variant="outline"
                size="icon-sm"
                :disabled="!paginator.links[0]?.url"
            >
                <Link
                    :href="paginator.links[0]?.url ?? '#'"
                    preserve-scroll
                    aria-label="Previous page"
                >
                    <ChevronLeft class="size-4" />
                </Link>
            </Button>
            <template
                v-for="link in paginator.links.slice(1, -1)"
                :key="link.label"
            >
                <Button
                    v-if="link.url"
                    as-child
                    :variant="link.active ? 'default' : 'ghost'"
                    size="icon-sm"
                >
                    <Link :href="link.url" preserve-scroll>{{
                        link.label
                    }}</Link>
                </Button>
                <span v-else class="px-1" v-html="link.label" />
            </template>
            <Button
                as-child
                variant="outline"
                size="icon-sm"
                :disabled="!paginator.links[paginator.links.length - 1]?.url"
            >
                <Link
                    :href="
                        paginator.links[paginator.links.length - 1]?.url ?? '#'
                    "
                    preserve-scroll
                    aria-label="Next page"
                >
                    <ChevronRight class="size-4" />
                </Link>
            </Button>
        </div>
    </div>
</template>
