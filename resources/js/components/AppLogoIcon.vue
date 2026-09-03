<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <img
        src="/eagles-logo.png"
        alt=""
        class="object-contain"
        :class="className"
        v-bind="$attrs"
        aria-hidden="true"
    />
</template>
