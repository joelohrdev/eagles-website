<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import CouponController from '@/actions/App/Http/Controllers/Admin/CouponController';
import MoneyInput from '@/components/admin/MoneyInput.vue';
import DateTimePicker from '@/components/DateTimePicker.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import type { AdminCoupon, CouponFormOptions } from '@/types/coupons';

const props = withDefaults(
    defineProps<
        CouponFormOptions & {
            coupon?: AdminCoupon | null;
        }
    >(),
    { coupon: null },
);

const type = ref(props.coupon?.type ?? 'percent');
const appliesTo = ref(props.coupon?.applies_to ?? 'all');
const isActive = ref(props.coupon?.is_active ?? true);
const campIds = ref<number[]>(props.coupon?.camp_ids ?? []);

const isPercent = computed(() => type.value === 'percent');
const offersCamps = computed(() => appliesTo.value !== 'merch');

function toggleCamp(id: number, checked: boolean) {
    campIds.value = checked
        ? [...campIds.value, id]
        : campIds.value.filter((campId) => campId !== id);
}

const formProps = props.coupon
    ? CouponController.update.form(props.coupon)
    : CouponController.store.form();
</script>

<template>
    <Form v-bind="formProps" class="space-y-8" v-slot="{ errors, processing }">
        <div class="grid gap-6 md:grid-cols-2">
            <div class="grid content-start gap-2">
                <Label for="code">Code</Label>
                <Input
                    id="code"
                    name="code"
                    :default-value="coupon?.code ?? ''"
                    required
                    placeholder="THANKS30"
                    class="uppercase"
                    autocomplete="off"
                />
                <p class="text-xs text-muted-foreground">
                    Customers can type it in any case.
                </p>
                <InputError :message="errors.code" />
            </div>
            <div class="grid content-start gap-2">
                <Label for="description"
                    >Note
                    <span class="text-muted-foreground"
                        >(only staff see this)</span
                    ></Label
                >
                <Input
                    id="description"
                    name="description"
                    :default-value="coupon?.description ?? ''"
                    placeholder="Eagles families — Thanksgiving camp"
                />
                <InputError :message="errors.description" />
            </div>
        </div>

        <div class="grid gap-6 md:grid-cols-2">
            <div class="grid content-start gap-2">
                <Label for="type">Discount</Label>
                <input type="hidden" name="type" :value="type" />
                <Select v-model="type">
                    <SelectTrigger id="type" class="w-full"
                        ><SelectValue
                    /></SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="option in types"
                            :key="option.value"
                            :value="option.value"
                            >{{ option.label }}</SelectItem
                        >
                    </SelectContent>
                </Select>
                <p class="text-xs text-muted-foreground">
                    “Each item” means each camp session, or each merch item. A
                    coupon is never combined with a camp’s multi-session price —
                    the customer gets whichever is lower.
                </p>
                <InputError :message="errors.type" />
            </div>
            <div class="grid content-start gap-2">
                <Label for="value">{{
                    isPercent ? 'Percent off' : 'Amount off (USD)'
                }}</Label>
                <div v-if="isPercent" class="relative">
                    <Input
                        id="value"
                        name="value"
                        type="text"
                        inputmode="numeric"
                        autocomplete="off"
                        class="pr-8"
                        :default-value="coupon?.value ?? ''"
                        placeholder="20"
                        required
                    />
                    <span
                        class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-muted-foreground"
                        aria-hidden="true"
                        >%</span
                    >
                </div>
                <MoneyInput
                    v-else
                    id="value"
                    name="value"
                    :default-value="coupon?.value"
                    placeholder="25.00"
                    required
                />
                <InputError :message="errors.value" />
            </div>
        </div>

        <div class="grid content-start gap-2">
            <Label for="applies_to">Works for</Label>
            <input type="hidden" name="applies_to" :value="appliesTo" />
            <Select v-model="appliesTo">
                <SelectTrigger id="applies_to" class="w-full md:w-1/2"
                    ><SelectValue
                /></SelectTrigger>
                <SelectContent>
                    <SelectItem
                        v-for="option in scopes"
                        :key="option.value"
                        :value="option.value"
                        >{{ option.label }}</SelectItem
                    >
                </SelectContent>
            </Select>
            <InputError :message="errors.applies_to" />
        </div>

        <fieldset v-if="offersCamps && camps.length > 0" class="space-y-3">
            <legend class="text-sm font-medium">Limit to camps</legend>
            <p class="text-xs text-muted-foreground">
                Leave all unticked to allow every camp.
            </p>
            <div class="grid gap-2 sm:grid-cols-2">
                <label
                    v-for="camp in camps"
                    :key="camp.id"
                    class="flex items-center gap-2 text-sm"
                >
                    <Checkbox
                        :model-value="campIds.includes(camp.id)"
                        @update:model-value="
                            (checked) => toggleCamp(camp.id, checked === true)
                        "
                    />
                    {{ camp.name }}
                </label>
            </div>
            <input
                v-for="id in campIds"
                :key="id"
                type="hidden"
                name="camp_ids[]"
                :value="id"
            />
            <InputError :message="errors.camp_ids" />
        </fieldset>

        <div class="grid gap-6 md:grid-cols-3">
            <DateTimePicker
                name="starts_at"
                label="Starts"
                :model-value="coupon?.starts_at"
                optional
                :error="errors.starts_at"
                hint="Leave blank to work right away."
            />
            <DateTimePicker
                name="expires_at"
                label="Expires"
                :model-value="coupon?.expires_at"
                optional
                :error="errors.expires_at"
                hint="Leave blank to never expire."
            />
            <div class="grid content-start gap-2">
                <Label for="max_redemptions"
                    >Use limit
                    <span class="text-muted-foreground">(optional)</span></Label
                >
                <Input
                    id="max_redemptions"
                    name="max_redemptions"
                    type="number"
                    min="1"
                    :default-value="coupon?.max_redemptions ?? ''"
                    placeholder="Unlimited"
                />
                <p v-if="coupon" class="text-xs text-muted-foreground">
                    Used {{ coupon.redemptions_count }}
                    {{ coupon.redemptions_count === 1 ? 'time' : 'times' }}
                    so far. Cancelled and refunded orders don’t count.
                </p>
                <InputError :message="errors.max_redemptions" />
            </div>
        </div>

        <div class="flex items-center gap-3 rounded-md border p-4">
            <Switch id="is_active" v-model="isActive" />
            <input type="hidden" name="is_active" :value="isActive ? 1 : 0" />
            <div>
                <Label for="is_active" class="cursor-pointer">Active</Label>
                <p class="text-xs text-muted-foreground">
                    Inactive codes are turned down at checkout.
                </p>
            </div>
        </div>

        <div class="flex items-center gap-3 border-t pt-6">
            <Button :disabled="processing">{{
                coupon ? 'Save changes' : 'Create coupon'
            }}</Button>
            <slot name="actions" />
        </div>
    </Form>
</template>
