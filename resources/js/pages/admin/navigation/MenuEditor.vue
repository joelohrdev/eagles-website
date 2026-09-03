<script setup lang="ts">
import { Form, router } from '@inertiajs/vue3';
import {
    ExternalLink,
    EyeOff,
    GripVertical,
    Pencil,
    Plus,
    X,
} from '@lucide/vue';
import type Sortable from 'sortablejs';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import NavigationController from '@/actions/App/Http/Controllers/Admin/NavigationController';
import ConfirmDelete from '@/components/admin/ConfirmDelete.vue';
import EmptyState from '@/components/admin/EmptyState.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import LinkFields from '@/pages/admin/navigation/LinkFields.vue';
import type {
    MenuItem,
    MenuLocation,
    PageOption,
} from '@/pages/admin/navigation/types';
import { reorder } from '@/routes/admin/navigation';

const props = defineProps<{
    location: MenuLocation;
    title: string;
    description: string;
    items: MenuItem[];
    pages: PageOption[];
    maxItems?: number;
}>();

const list = ref<HTMLElement | null>(null);
const editingId = ref<number | null>(null);
const adding = ref(false);
const saving = ref(false);
let sortable: Sortable | null = null;

const localItems = ref<MenuItem[]>([...props.items]);

watch(
    () => props.items,
    (items) => {
        localItems.value = [...items];
    },
);

const canAdd = computed(
    () => !props.maxItems || localItems.value.length < props.maxItems,
);

function pageLabel(routeName: string | null): string {
    return (
        props.pages.find((p) => p.value === routeName)?.label ?? routeName ?? ''
    );
}

function persistOrder(order: number[]) {
    saving.value = true;
    router.post(
        reorder.url(),
        { location: props.location, order },
        {
            preserveScroll: true,
            preserveState: true,
            onFinish: () => {
                saving.value = false;
            },
        },
    );
}

onMounted(async () => {
    if (!list.value) {
        return;
    }

    // Loaded on the client only so the page stays SSR-safe.
    const { default: SortableJs } = await import('sortablejs');

    sortable = SortableJs.create(list.value, {
        handle: '[data-drag-handle]',
        animation: 150,
        ghostClass: 'opacity-40',
        onEnd: (event) => {
            if (
                event.oldIndex === undefined ||
                event.newIndex === undefined ||
                event.oldIndex === event.newIndex
            ) {
                return;
            }

            const next = [...localItems.value];
            const [moved] = next.splice(event.oldIndex, 1);
            next.splice(event.newIndex, 0, moved);
            localItems.value = next;
            persistOrder(next.map((item) => item.id));
        },
    });
});

onBeforeUnmount(() => {
    sortable?.destroy();
});

function move(index: number, delta: number) {
    const target = index + delta;

    if (target < 0 || target >= localItems.value.length) {
        return;
    }

    const next = [...localItems.value];
    const [moved] = next.splice(index, 1);
    next.splice(target, 0, moved);
    localItems.value = next;
    persistOrder(next.map((item) => item.id));
}
</script>

<template>
    <section class="rounded-lg border bg-card">
        <header
            class="flex flex-wrap items-start justify-between gap-3 border-b p-4"
        >
            <div>
                <h2 class="font-semibold">{{ title }}</h2>
                <p class="text-sm text-muted-foreground">{{ description }}</p>
            </div>
            <Button
                v-if="canAdd"
                type="button"
                size="sm"
                variant="outline"
                @click="adding = !adding"
            >
                <Plus class="size-4" /> Add link
            </Button>
        </header>

        <Form
            v-if="adding"
            v-bind="NavigationController.store.form()"
            :options="{ preserveScroll: true }"
            class="grid gap-4 border-b bg-muted/40 p-4 sm:grid-cols-12"
            v-slot="{ errors, processing }"
            @success="adding = false"
        >
            <input type="hidden" name="location" :value="location" />
            <LinkFields :pages="pages" :errors="errors" />
            <div class="flex items-end gap-2 sm:col-span-12">
                <Button type="submit" size="sm" :disabled="processing"
                    >Add link</Button
                >
                <Button
                    type="button"
                    size="sm"
                    variant="ghost"
                    @click="adding = false"
                    >Cancel</Button
                >
            </div>
        </Form>

        <EmptyState
            v-if="localItems.length === 0"
            title="No links yet"
            description="Add a link to get started."
            class="m-4"
        />

        <ul
            v-else
            ref="list"
            class="divide-y"
            :class="{ 'opacity-60': saving }"
        >
            <li
                v-for="(item, index) in localItems"
                :key="item.id"
                class="p-3"
                :data-id="item.id"
            >
                <div
                    v-if="editingId !== item.id"
                    class="flex items-center gap-3"
                >
                    <button
                        type="button"
                        data-drag-handle
                        class="cursor-grab touch-none rounded p-1 text-muted-foreground hover:bg-accent hover:text-foreground active:cursor-grabbing"
                        aria-label="Drag to reorder"
                    >
                        <GripVertical class="size-4" />
                    </button>
                    <div
                        class="flex min-w-0 flex-1 flex-wrap items-center gap-x-3 gap-y-1"
                    >
                        <span
                            class="font-medium"
                            :class="{
                                'text-muted-foreground line-through':
                                    !item.is_visible,
                            }"
                        >
                            {{ item.label }}
                        </span>
                        <span class="truncate text-xs text-muted-foreground">
                            <template v-if="item.link_type === 'page'"
                                >{{ pageLabel(item.route_name) }} page</template
                            >
                            <template v-else>{{ item.url }}</template>
                        </span>
                        <Badge
                            v-if="!item.is_visible"
                            variant="outline"
                            class="gap-1"
                            ><EyeOff class="size-3" /> Hidden</Badge
                        >
                        <Badge
                            v-if="item.opens_in_new_tab"
                            variant="secondary"
                            class="gap-1"
                            ><ExternalLink class="size-3" /> New tab</Badge
                        >
                    </div>
                    <div class="flex items-center gap-1">
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon-sm"
                            :disabled="index === 0"
                            aria-label="Move up"
                            @click="move(index, -1)"
                            >↑</Button
                        >
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon-sm"
                            :disabled="index === localItems.length - 1"
                            aria-label="Move down"
                            @click="move(index, 1)"
                            >↓</Button
                        >
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon-sm"
                            aria-label="Edit"
                            @click="editingId = item.id"
                            ><Pencil class="size-4"
                        /></Button>
                        <ConfirmDelete
                            :href="NavigationController.destroy.url(item.id)"
                            size="icon-sm"
                            title="Remove this link?"
                            description="It will disappear from the site immediately."
                        />
                    </div>
                </div>

                <Form
                    v-else
                    v-bind="NavigationController.update.form(item.id)"
                    :options="{ preserveScroll: true }"
                    class="grid gap-4 rounded-md bg-muted/40 p-3 sm:grid-cols-12"
                    v-slot="{ errors, processing }"
                    @success="editingId = null"
                >
                    <LinkFields :pages="pages" :errors="errors" :item="item" />
                    <div class="flex items-end gap-2 sm:col-span-12">
                        <Button type="submit" size="sm" :disabled="processing"
                            >Save</Button
                        >
                        <Button
                            type="button"
                            size="sm"
                            variant="ghost"
                            @click="editingId = null"
                            ><X class="size-4" /> Cancel</Button
                        >
                    </div>
                </Form>
            </li>
        </ul>
    </section>
</template>
