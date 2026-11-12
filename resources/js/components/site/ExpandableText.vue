<script setup lang="ts">
import { ChevronDown } from '@lucide/vue';
import { onBeforeUnmount, onMounted, ref, useId } from 'vue';
import { Button } from '@/components/ui/button';

/**
 * Long text shown a few lines tall with a "Read more" toggle. The button only
 * appears when the text is actually cut off, and the full text is always in
 * the page (search engines and screen readers get all of it).
 */
const props = withDefaults(
    defineProps<{
        text: string;
        /** Lines shown before "Read more"; must match a line-clamp-* class below. */
        lines?: 4 | 6 | 8;
    }>(),
    { lines: 6 },
);

const CLAMP: Record<number, string> = {
    4: 'line-clamp-4',
    6: 'line-clamp-6',
    8: 'line-clamp-8',
};

const contentId = useId();
const content = ref<HTMLElement | null>(null);
const expanded = ref(false);
const overflows = ref(false);

let observer: ResizeObserver | null = null;

function measure() {
    if (!content.value || expanded.value) {
        return;
    }

    overflows.value =
        content.value.scrollHeight > content.value.clientHeight + 1;
}

onMounted(() => {
    measure();
    observer = new ResizeObserver(measure);

    if (content.value) {
        observer.observe(content.value);
    }
});

onBeforeUnmount(() => observer?.disconnect());
</script>

<template>
    <div>
        <div class="relative">
            <p
                :id="contentId"
                ref="content"
                class="whitespace-pre-line"
                :class="expanded ? '' : CLAMP[props.lines]"
            >
                {{ text }}
            </p>
            <!-- Fade the last lines so it's obvious the text continues. -->
            <div
                v-if="overflows && !expanded"
                class="pointer-events-none absolute inset-x-0 bottom-0 h-16 bg-gradient-to-t from-background to-transparent"
                aria-hidden="true"
            />
        </div>
        <Button
            v-if="overflows || expanded"
            type="button"
            variant="outline"
            class="mt-3 border-sky font-semibold text-navy hover:bg-sky hover:text-navy dark:text-snow"
            :aria-expanded="expanded"
            :aria-controls="contentId"
            @click="expanded = !expanded"
        >
            {{ expanded ? 'Show less' : 'Read more' }}
            <ChevronDown
                class="size-4 transition-transform"
                :class="{ 'rotate-180': expanded }"
            />
        </Button>
    </div>
</template>
