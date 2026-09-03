<script setup lang="ts">
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import type { RegistrationState } from '@/types';

const props = defineProps<{
    state: RegistrationState;
    spotsRemaining?: number | null;
}>();

const label = computed(() => {
    switch (props.state) {
        case 'open':
            return props.spotsRemaining !== null &&
                props.spotsRemaining !== undefined
                ? `Registration open · ${props.spotsRemaining} spots left`
                : 'Registration open';
        case 'upcoming':
            return 'Registration opens soon';
        case 'full':
            return 'Full';
        default:
            return 'Registration closed';
    }
});

const classes = computed(() => {
    switch (props.state) {
        case 'open':
            return 'bg-sky text-navy hover:bg-sky';
        case 'upcoming':
            return 'bg-accent text-navy hover:bg-accent';
        default:
            return 'bg-secondary text-muted-foreground hover:bg-secondary';
    }
});
</script>

<template>
    <Badge :class="classes">{{ label }}</Badge>
</template>
