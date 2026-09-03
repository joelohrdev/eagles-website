<script setup lang="ts">
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';

const props = defineProps<{
    status: string;
    label?: string;
}>();

const variant = computed(() => {
    switch (props.status) {
        case 'paid':
        case 'fulfilled':
        case 'open':
        case 'active':
        case 'published':
            return 'default';
        case 'pending':
        case 'pending_payment':
        case 'upcoming':
            return 'secondary';
        case 'cancelled':
        case 'refunded':
        case 'closed':
        case 'full':
        case 'inactive':
        case 'draft':
            return 'outline';
        default:
            return 'secondary';
    }
});

const text = computed(() => props.label ?? props.status.replace(/_/g, ' '));
</script>

<template>
    <Badge :variant="variant" class="capitalize">{{ text }}</Badge>
</template>
