<script setup lang="ts">
import { Form, useHttp } from '@inertiajs/vue3';
import { computed, onMounted, ref, watch } from 'vue';
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
import { money } from '@/lib/format';
import type {
    AdminCoupon,
    CouponFormOptions,
    CouponPreview,
} from '@/types/coupons';

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
const amount = ref(props.coupon?.value ?? '');
const combinable = ref(props.coupon?.combinable_with_multi_session ?? false);

const isPercent = computed(() => type.value === 'percent');
const offersCamps = computed(() => appliesTo.value !== 'merch');

function toggleCamp(id: number, checked: boolean) {
    campIds.value = checked
        ? [...campIds.value, id]
        : campIds.value.filter((campId) => campId !== id);
}

/**
 * Live price preview: the server prices a sample camp with the coupon as it
 * stands, through the same pricing as checkout, so nothing is worked out here.
 */
const http = useHttp<
    {
        type: string;
        value: string;
        combinable_with_multi_session: boolean;
        camp_ids: number[];
    },
    CouponPreview
>({ type: '', value: '', combinable_with_multi_session: false, camp_ids: [] });
const preview = ref<CouponPreview | null>(null);
let previewTimer: ReturnType<typeof setTimeout> | undefined;

async function refreshPreview() {
    const value = String(amount.value).replace(/[$,\s]/g, '');

    if (!offersCamps.value || value === '' || Number.isNaN(Number(value))) {
        preview.value = null;

        return;
    }

    http.type = type.value;
    http.value = value;
    http.combinable_with_multi_session = combinable.value;
    http.camp_ids = campIds.value;

    try {
        preview.value =
            (await http.post(CouponController.preview.url())) ?? null;
    } catch {
        preview.value = null;
    }
}

watch([type, amount, combinable, campIds, appliesTo], () => {
    clearTimeout(previewTimer);
    previewTimer = setTimeout(refreshPreview, 300);
});

onMounted(refreshPreview);

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
                    “Each item” means each camp session, or each merch item.
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
                        v-model="amount"
                        name="value"
                        type="text"
                        inputmode="numeric"
                        autocomplete="off"
                        class="pr-8"
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
                    v-model="amount"
                    name="value"
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

        <div v-if="offersCamps" class="grid gap-4 lg:grid-cols-2">
            <div class="flex items-start gap-3 rounded-md border p-4">
                <Switch
                    id="combinable_with_multi_session"
                    v-model="combinable"
                />
                <input
                    type="hidden"
                    name="combinable_with_multi_session"
                    :value="combinable ? 1 : 0"
                />
                <div>
                    <Label
                        for="combinable_with_multi_session"
                        class="cursor-pointer"
                        >Can be combined with multi-session discount</Label
                    >
                    <p class="mt-1 text-xs text-muted-foreground">
                        On: a family booking every session gets the
                        multi-session price, then this coupon off that. Off:
                        they get whichever single discount saves more, so a
                        promo price stays the same per session.
                    </p>
                </div>
            </div>

            <div class="rounded-md border p-4" aria-live="polite">
                <p class="text-sm font-medium">Price preview</p>
                <p class="text-xs text-muted-foreground">
                    {{
                        preview?.camp
                            ? preview.camp
                            : 'Sample camp: $125 per session, $225 for both'
                    }}
                </p>
                <dl v-if="preview" class="mt-3 space-y-3 text-sm">
                    <div
                        v-for="scenario in preview.scenarios"
                        :key="scenario.label"
                    >
                        <div class="flex justify-between gap-3">
                            <dt>{{ scenario.label }}</dt>
                            <dd class="font-semibold">
                                {{ money(scenario.total) }}
                                <span
                                    v-if="scenario.total !== scenario.subtotal"
                                    class="font-normal text-muted-foreground line-through"
                                    >{{ money(scenario.subtotal) }}</span
                                >
                            </dd>
                        </div>
                        <p class="text-xs text-muted-foreground">
                            {{
                                scenario.discounts.length > 0
                                    ? scenario.discounts
                                          .map(
                                              (discount) =>
                                                  `${discount.label} −${money(discount.amount)}`,
                                          )
                                          .join(' · ')
                                    : 'No discount'
                            }}
                        </p>
                        <p v-if="scenario.note" class="text-xs text-sky-dark">
                            {{ scenario.note }}
                        </p>
                    </div>
                </dl>
                <p v-else class="mt-3 text-xs text-muted-foreground">
                    Enter a discount to see what families would pay.
                </p>
            </div>
        </div>

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
