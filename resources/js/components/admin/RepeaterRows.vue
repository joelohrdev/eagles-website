<script setup lang="ts">
import { Plus, Trash2 } from '@lucide/vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';

export type RepeaterRow = { title: string; description: string };

/**
 * A repeatable list of title/description rows posted as `name[i][title]` /
 * `name[i][description]`, matching UpdateSiteSettingsRequest::listRules().
 */
withDefaults(
    defineProps<{
        name: string;
        label: string;
        max: number;
        errors: Record<string, string | undefined>;
        hint?: string;
        titleLabel?: string;
        descriptionLabel?: string;
    }>(),
    { hint: undefined, titleLabel: 'Title', descriptionLabel: 'Description' },
);

const rows = defineModel<RepeaterRow[]>({ required: true });
</script>

<template>
    <section class="space-y-4 border-t pt-6">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h3 class="text-sm font-semibold">{{ label }}</h3>
                <p v-if="hint" class="text-xs text-muted-foreground">
                    {{ hint }}
                </p>
            </div>
            <Button
                type="button"
                variant="outline"
                size="sm"
                :disabled="rows.length >= max"
                @click="rows.push({ title: '', description: '' })"
            >
                <Plus class="size-4" /> Add item
            </Button>
        </div>
        <InputError :message="errors[name]" />
        <div
            v-for="(row, i) in rows"
            :key="i"
            class="grid gap-3 rounded-md border p-3 sm:grid-cols-[1fr_auto]"
        >
            <div class="grid content-start gap-2">
                <Label :for="`${name}_title_${i}`">{{ titleLabel }}</Label>
                <Input
                    :id="`${name}_title_${i}`"
                    v-model="row.title"
                    :name="`${name}[${i}][title]`"
                    required
                />
                <InputError :message="errors[`${name}.${i}.title`]" />
            </div>
            <Button
                type="button"
                variant="ghost"
                size="icon"
                class="mt-6 text-destructive"
                aria-label="Remove item"
                @click="rows.splice(i, 1)"
            >
                <Trash2 class="size-4" />
            </Button>
            <div class="grid content-start gap-2 sm:col-span-2">
                <Label :for="`${name}_desc_${i}`">{{ descriptionLabel }}</Label>
                <Textarea
                    :id="`${name}_desc_${i}`"
                    v-model="row.description"
                    :name="`${name}[${i}][description]`"
                    rows="2"
                />
                <InputError :message="errors[`${name}.${i}.description`]" />
            </div>
        </div>
        <p v-if="rows.length === 0" class="text-sm text-muted-foreground">
            No items yet — add up to {{ max }}.
        </p>
    </section>
</template>
