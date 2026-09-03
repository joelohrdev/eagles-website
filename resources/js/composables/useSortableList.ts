import { router } from '@inertiajs/vue3';
import { unrefElement } from '@vueuse/core';
import type Sortable from 'sortablejs';
import { onBeforeUnmount, onMounted, ref, shallowRef, watch } from 'vue';
import type { ComponentPublicInstance, Ref } from 'vue';

type Identifiable = { id: number };

/**
 * Drag-and-drop reordering for an admin list.
 *
 * Bind `container` to the element wrapping the rows and `data-drag-handle` to
 * the grab handle inside each row, then render `items` instead of the prop.
 * The new order is applied optimistically and posted to `url` as `order`.
 */
export function useSortableList<T extends Identifiable>(
    source: () => T[],
    url: string,
    extraData: Record<string, unknown> = {},
) {
    const container = ref<HTMLElement | ComponentPublicInstance | null>(null);
    const items = ref([...source()]) as Ref<T[]>;
    const sortable = shallowRef<Sortable | null>(null);

    watch(source, (next) => {
        items.value = [...next];
    });

    function persist(order: number[]): void {
        router.post(
            url,
            { ...extraData, order },
            { preserveScroll: true, preserveState: true },
        );
    }

    function moveTo(from: number, to: number): void {
        if (to < 0 || to >= items.value.length || from === to) {
            return;
        }

        const next = [...items.value];
        const [moved] = next.splice(from, 1);
        next.splice(to, 0, moved);
        items.value = next;
        persist(next.map((item) => item.id));
    }

    function move(index: number, delta: -1 | 1): void {
        moveTo(index, index + delta);
    }

    onMounted(async () => {
        const element = unrefElement(container) as HTMLElement | undefined;

        if (!element) {
            return;
        }

        // Loaded on the client only so the page stays SSR-safe.
        const { default: SortableJs } = await import('sortablejs');

        sortable.value = SortableJs.create(element, {
            handle: '[data-drag-handle]',
            animation: 150,
            ghostClass: 'opacity-40',
            onEnd: (event) => {
                if (
                    event.oldIndex === undefined ||
                    event.newIndex === undefined
                ) {
                    return;
                }

                moveTo(event.oldIndex, event.newIndex);
            },
        });
    });

    onBeforeUnmount(() => {
        sortable.value?.destroy();
    });

    return { container, items, move };
}
