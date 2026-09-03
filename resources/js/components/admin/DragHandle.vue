<script setup lang="ts">
import { GripVertical } from '@lucide/vue';

defineProps<{
    label: string;
}>();

const emit = defineEmits<{
    move: [delta: -1 | 1];
}>();
</script>

<template>
    <button
        type="button"
        data-drag-handle
        class="flex size-8 cursor-grab items-center justify-center rounded-md text-muted-foreground hover:bg-accent hover:text-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none active:cursor-grabbing"
        :aria-label="`Reorder ${label}: drag, or press the up and down arrow keys`"
        @keydown.up.prevent="emit('move', -1)"
        @keydown.down.prevent="emit('move', 1)"
    >
        <GripVertical class="size-4" />
    </button>
</template>
