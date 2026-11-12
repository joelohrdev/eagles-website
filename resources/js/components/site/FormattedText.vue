<script setup lang="ts">
import { computed } from 'vue';

/**
 * Plain text from an admin textarea, shown the way it was typed: blank lines
 * start new paragraphs, single line breaks are kept, and runs of lines that
 * start with "- ", "* " or "• " become a bulleted list. Rendered as Vue
 * elements (never v-html), so nothing typed can inject markup.
 */
const props = defineProps<{
    text: string;
}>();

type Block =
    { type: 'paragraph'; text: string } | { type: 'list'; items: string[] };

const BULLET = /^\s*[-*•]\s+/;

const blocks = computed<Block[]>(() => {
    const result: Block[] = [];
    let paragraph: string[] = [];
    let list: string[] = [];

    const flushParagraph = () => {
        if (paragraph.length > 0) {
            result.push({ type: 'paragraph', text: paragraph.join('\n') });
            paragraph = [];
        }
    };

    const flushList = () => {
        if (list.length > 0) {
            result.push({ type: 'list', items: list });
            list = [];
        }
    };

    for (const line of props.text.replace(/\r\n?/g, '\n').split('\n')) {
        if (line.trim() === '') {
            flushParagraph();
            flushList();
        } else if (BULLET.test(line)) {
            flushParagraph();
            list.push(line.replace(BULLET, ''));
        } else {
            flushList();
            paragraph.push(line);
        }
    }

    flushParagraph();
    flushList();

    return result;
});
</script>

<template>
    <div class="space-y-3">
        <template v-for="(block, index) in blocks" :key="index">
            <ul
                v-if="block.type === 'list'"
                class="list-disc space-y-1 pl-5 marker:text-sky"
            >
                <li v-for="(item, i) in block.items" :key="i">{{ item }}</li>
            </ul>
            <p v-else class="whitespace-pre-line">{{ block.text }}</p>
        </template>
    </div>
</template>
