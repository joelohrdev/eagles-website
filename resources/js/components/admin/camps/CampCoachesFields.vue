<script setup lang="ts">
import { ArrowDown, ArrowUp, Plus, Trash2 } from '@lucide/vue';
import { reactive } from 'vue';
import ImageUpload from '@/components/admin/ImageUpload.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import type { CampCoach } from '@/types/camps';

/**
 * The coaches shown on a camp's page. Each row is a named input inside the
 * camp <Form> (coaches[i][...]); the row order is the order on the page,
 * changed with the arrow buttons.
 */
const props = defineProps<{
    coaches?: CampCoach[];
    errors: Record<string, string>;
}>();

type CoachRow = {
    uid: string;
    id: number | null;
    name: string;
    title: string;
    bio: string;
    photo_url: string | null;
};

let counter = 0;

const rows = reactive<CoachRow[]>(
    (props.coaches ?? []).map((coach) => ({
        uid: `coach-${coach.id}`,
        id: coach.id,
        name: coach.name,
        title: coach.title ?? '',
        bio: coach.bio ?? '',
        photo_url: coach.photo_url,
    })),
);

function addCoach() {
    rows.push({
        uid: `coach-new-${++counter}`,
        id: null,
        name: '',
        title: '',
        bio: '',
        photo_url: null,
    });
}

function move(index: number, offset: number) {
    const target = index + offset;

    if (target < 0 || target >= rows.length) {
        return;
    }

    const [row] = rows.splice(index, 1);
    rows.splice(target, 0, row);
}

function error(path: string): string | undefined {
    return props.errors[path];
}
</script>

<template>
    <div class="space-y-6">
        <div>
            <h3 class="text-sm font-medium">Camp coaches</h3>
            <p class="text-xs text-muted-foreground">
                Shown on the camp page as photos families can click for the
                coach’s bio. Each camp has its own list, so guest instructors
                don’t need to be on the main coaching staff page.
            </p>
        </div>

        <div
            v-for="(coach, index) in rows"
            :key="coach.uid"
            class="space-y-4 rounded-lg border p-4"
        >
            <input
                v-if="coach.id"
                type="hidden"
                :name="`coaches[${index}][id]`"
                :value="coach.id"
            />

            <div class="flex items-start justify-between gap-3">
                <p class="text-sm font-medium">
                    {{ coach.name || 'New coach' }}
                </p>
                <div class="flex gap-1">
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        :disabled="index === 0"
                        :aria-label="`Move ${coach.name || 'coach'} up`"
                        @click="move(index, -1)"
                        ><ArrowUp class="size-4"
                    /></Button>
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        :disabled="index === rows.length - 1"
                        :aria-label="`Move ${coach.name || 'coach'} down`"
                        @click="move(index, 1)"
                        ><ArrowDown class="size-4"
                    /></Button>
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        :aria-label="`Remove ${coach.name || 'coach'}`"
                        @click="rows.splice(index, 1)"
                        ><Trash2 class="size-4"
                    /></Button>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-[12rem_1fr]">
                <ImageUpload
                    :name="`coaches[${index}][photo]`"
                    :remove-name="`coaches[${index}][remove_photo]`"
                    label="Photo"
                    aspect="square"
                    :current-url="coach.photo_url"
                    :error="error(`coaches.${index}.photo`)"
                    hint="A headshot works best. JPG, PNG, or WebP up to 5 MB."
                />

                <div class="space-y-4">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid content-start gap-1">
                            <Label :for="`coach-name-${coach.uid}`">Name</Label>
                            <Input
                                :id="`coach-name-${coach.uid}`"
                                v-model="coach.name"
                                :name="`coaches[${index}][name]`"
                                required
                                placeholder="Coach Smith"
                            />
                            <InputError
                                :message="error(`coaches.${index}.name`)"
                            />
                        </div>
                        <div class="grid content-start gap-1">
                            <Label :for="`coach-title-${coach.uid}`"
                                >Role
                                <span class="text-muted-foreground"
                                    >(optional)</span
                                ></Label
                            >
                            <Input
                                :id="`coach-title-${coach.uid}`"
                                v-model="coach.title"
                                :name="`coaches[${index}][title]`"
                                placeholder="Pitching Instructor"
                            />
                            <InputError
                                :message="error(`coaches.${index}.title`)"
                            />
                        </div>
                    </div>
                    <div class="grid content-start gap-1">
                        <Label :for="`coach-bio-${coach.uid}`"
                            >Bio
                            <span class="text-muted-foreground"
                                >(optional)</span
                            ></Label
                        >
                        <Textarea
                            :id="`coach-bio-${coach.uid}`"
                            v-model="coach.bio"
                            :name="`coaches[${index}][bio]`"
                            rows="5"
                            placeholder="Playing and coaching background, what they focus on with players…"
                        />
                        <InputError :message="error(`coaches.${index}.bio`)" />
                    </div>
                </div>
            </div>
        </div>

        <Button type="button" variant="outline" @click="addCoach"
            ><Plus class="size-4" /> Add coach</Button
        >
    </div>
</template>
