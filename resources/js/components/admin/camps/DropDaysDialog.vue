<script setup lang="ts">
import { router, useHttp } from '@inertiajs/vue3';
import { CalendarMinus } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import MoneyInput from '@/components/admin/MoneyInput.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { money } from '@/lib/format';
import { dropDays } from '@/routes/admin/camps/registrations';
import { preview as previewDropDays } from '@/routes/admin/camps/registrations/drop-days';

type DropPreview = {
    kept_day_ids: number[];
    kept_price: number;
    paid: number;
    suggested_refund: number;
    max_refund: number;
};

const props = defineProps<{
    campSlug: string;
    registrationId: number;
    playerName: string;
    /** The days this player is booked on, with their labels. */
    days: { id: number; label: string }[];
}>();

const open = ref(false);
const dropped = ref<number[]>([]);
const refund = ref('');
const preview = ref<DropPreview | null>(null);
const submitting = ref(false);
const errors = ref<Record<string, string>>({});

/**
 * The server works out the price of the days kept and the suggested refund
 * (same pricing as sign-up); nothing is calculated here.
 */
const http = useHttp<{ days: number[] }, DropPreview>({ days: [] });

const args = computed(() => ({
    camp: props.campSlug,
    registration: props.registrationId,
}));

function toggle(id: number, checked: boolean) {
    dropped.value = checked
        ? [...dropped.value, id]
        : dropped.value.filter((dayId) => dayId !== id);
}

watch(dropped, async () => {
    errors.value = {};
    preview.value = null;

    if (dropped.value.length === 0) {
        return;
    }

    http.days = dropped.value;

    try {
        const result = await http.post(previewDropDays.url(args.value));

        if (result) {
            preview.value = result;
            refund.value = (result.suggested_refund / 100).toFixed(2);
        } else {
            errors.value = { ...http.errors };
        }
    } catch {
        errors.value = { days: 'Couldn’t work out the refund. Try again.' };
    }
});

watch(open, (isOpen) => {
    if (!isOpen) {
        dropped.value = [];
        refund.value = '';
        preview.value = null;
        errors.value = {};
    }
});

function confirm() {
    router.post(
        dropDays.url(args.value),
        { days: dropped.value, refund: refund.value || '0' },
        {
            preserveScroll: true,
            onStart: () => (submitting.value = true),
            onFinish: () => (submitting.value = false),
            onSuccess: () => (open.value = false),
            onError: (formErrors) => (errors.value = formErrors),
        },
    );
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogTrigger as-child>
            <Button
                variant="ghost"
                size="icon-sm"
                :aria-label="`Drop days and refund for ${playerName}`"
                title="Drop days and refund"
                ><CalendarMinus class="size-4"
            /></Button>
        </DialogTrigger>
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>Drop days for {{ playerName }}</DialogTitle>
                <DialogDescription>
                    Tick the days they can’t make. They’re taken off the roster,
                    the spots open up, and the family is emailed.
                </DialogDescription>
            </DialogHeader>

            <fieldset class="space-y-2">
                <legend class="sr-only">Days to drop</legend>
                <label
                    v-for="day in days"
                    :key="day.id"
                    class="flex items-center gap-2 text-sm"
                >
                    <Checkbox
                        :model-value="dropped.includes(day.id)"
                        @update:model-value="
                            (checked) => toggle(day.id, checked === true)
                        "
                    />
                    {{ day.label }}
                </label>
                <InputError :message="errors.days" />
            </fieldset>

            <div
                v-if="preview"
                class="space-y-3 rounded-md border p-3 text-sm"
                aria-live="polite"
            >
                <dl class="space-y-1">
                    <div class="flex justify-between gap-3">
                        <dt class="text-muted-foreground">Paid so far</dt>
                        <dd>{{ money(preview.paid) }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-muted-foreground">
                            Price of the days they keep
                        </dt>
                        <dd>{{ money(preview.kept_price) }}</dd>
                    </div>
                    <div class="flex justify-between gap-3 font-medium">
                        <dt>Suggested refund</dt>
                        <dd>{{ money(preview.suggested_refund) }}</dd>
                    </div>
                </dl>
                <div class="grid gap-1.5">
                    <Label for="refund">Refund</Label>
                    <MoneyInput id="refund" v-model="refund" />
                    <p class="text-xs text-muted-foreground">
                        Up to {{ money(preview.max_refund) }}. Enter 0 to change
                        the days without a refund.
                    </p>
                    <InputError :message="errors.refund" />
                </div>
            </div>

            <DialogFooter>
                <Button variant="outline" @click="open = false">Cancel</Button>
                <Button :disabled="!preview || submitting" @click="confirm">{{
                    Number(refund) > 0
                        ? `Drop days and refund ${money(Math.round(Number(refund) * 100))}`
                        : 'Drop days'
                }}</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
