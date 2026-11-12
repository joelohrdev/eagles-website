<script setup lang="ts">
import { Form, Link, useHttp } from '@inertiajs/vue3';
import { ArrowLeft, Lock, TicketPercent } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import CampRegistrationController from '@/actions/App/Http/Controllers/Site/CampRegistrationController';
import DateTimePicker from '@/components/DateTimePicker.vue';
import InputError from '@/components/InputError.vue';
import RequiredMark from '@/components/RequiredMark.vue';
import CampRegistrationSummary from '@/components/site/CampRegistrationSummary.vue';
import PhoneInput from '@/components/site/PhoneInput.vue';
import PreviewBanner from '@/components/site/PreviewBanner.vue';
import RegistrationStateBadge from '@/components/site/RegistrationStateBadge.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { formatDateTime, money } from '@/lib/format';
import { contact } from '@/routes';
import type {
    CampDetail,
    CampPreview,
    CampQuote,
    CampScheduleDay,
    CampScheduleSession,
} from '@/types/camps';

const props = defineProps<{
    camp: CampDetail;
    /** Set on the staff-only preview, where drafts work and nothing can be submitted. */
    preview?: CampPreview | null;
    /** Where selections are priced; the preview uses an admin endpoint that also works for drafts. */
    quoteUrl?: string | null;
}>();

const campUrl = computed(() => props.preview?.camp_url ?? props.camp.url);

const hasGroups = computed(() => props.camp.age_groups.length > 0);
const ageGroupId = ref<number | null>(
    props.camp.age_groups.length === 1 ? props.camp.age_groups[0].id : null,
);

const onlyDay =
    props.camp.sessions.length === 1 && props.camp.sessions[0].days.length === 1
        ? props.camp.sessions[0].days[0].id
        : null;
const selectedDays = ref<number[]>(onlyDay ? [onlyDay] : []);

const couponInput = ref('');
const appliedCode = ref('');

const http = useHttp<
    { days: number[]; camp_age_group_id: number | null; coupon_code: string },
    CampQuote
>({ days: [], camp_age_group_id: null, coupon_code: '' });
const quote = ref<CampQuote | null>(null);

/** Places left for the chosen age group on a day; null means no limit. */
function remaining(day: CampScheduleDay): number | null {
    const groupId = hasGroups.value ? ageGroupId.value : null;

    return (
        day.remaining.find((slot) => slot.age_group_id === groupId)
            ?.remaining ?? null
    );
}

function isDayFull(day: CampScheduleDay): boolean {
    return (
        (!hasGroups.value || ageGroupId.value !== null) && remaining(day) === 0
    );
}

function timeFor(day: CampScheduleDay): string {
    const groupId = hasGroups.value ? ageGroupId.value : null;
    const time =
        day.times.find((t) => t.age_group_id === groupId) ??
        (hasGroups.value ? null : day.times[0]);

    return time?.range ?? '';
}

function sessionDayIds(session: CampScheduleSession): number[] {
    return session.days.map((day) => day.id);
}

function isWholeSession(session: CampScheduleSession): boolean {
    return sessionDayIds(session).every((id) =>
        selectedDays.value.includes(id),
    );
}

function allowsSingleDays(session: CampScheduleSession): boolean {
    return session.day_price !== null && session.days.length > 1;
}

function toggleSession(session: CampScheduleSession, checked: boolean) {
    const ids = sessionDayIds(session);
    const others = selectedDays.value.filter((id) => !ids.includes(id));

    selectedDays.value = checked ? [...others, ...ids] : others;
}

function toggleDay(day: CampScheduleDay, checked: boolean) {
    selectedDays.value = checked
        ? [...selectedDays.value, day.id]
        : selectedDays.value.filter((id) => id !== day.id);
}

async function refreshQuote() {
    if (
        selectedDays.value.length === 0 ||
        (hasGroups.value && ageGroupId.value === null)
    ) {
        quote.value = null;
        http.clearErrors();

        return;
    }

    http.days = selectedDays.value;
    http.camp_age_group_id = ageGroupId.value;
    http.coupon_code = appliedCode.value;

    /**
     * useHttp resolves with undefined on a 422 (the messages land in
     * http.errors) and only throws for network or server failures.
     */
    let result: CampQuote | undefined;

    try {
        result = await http.post(
            props.quoteUrl ??
                CampRegistrationController.quote.url(props.camp.slug),
        );
    } catch {
        result = undefined;
    }

    if (result) {
        quote.value = result;

        return;
    }

    quote.value = null;

    /** A bad code shouldn't block the price: drop it, price without it, and say why. */
    if (http.errors.coupon_code && appliedCode.value !== '') {
        const message = http.errors.coupon_code;
        appliedCode.value = '';
        await refreshQuote();
        couponError.value = message;
    }
}

const couponError = ref<string | null>(null);

function applyCoupon() {
    couponError.value = null;
    appliedCode.value = couponInput.value.trim();
    refreshQuote();
}

function removeCoupon() {
    couponInput.value = '';
    appliedCode.value = '';
    couponError.value = null;
    refreshQuote();
}

watch([selectedDays, ageGroupId], () => refreshQuote(), { immediate: true });

const summaryPrompt = computed(() =>
    hasGroups.value && ageGroupId.value === null
        ? 'Choose an age group and sessions to see your total.'
        : 'Choose sessions to see your total.',
);

const canSubmit = computed(() => quote.value !== null && !http.processing);

const today = new Date().toISOString().slice(0, 10);
const earliestBirthdate = `${new Date().getFullYear() - 25}-01-01`;
const defaultBirthMonth = `${new Date().getFullYear() - 12}-01-01`;
</script>

<template>
    <PreviewBanner
        v-if="preview"
        :preview="preview"
        note="Try the options — nothing is saved or charged."
    />

    <section class="bg-navy text-snow">
        <div class="container-site py-10 md:py-12">
            <Link
                :href="campUrl"
                class="inline-flex items-center gap-1 text-sm text-stone hover:text-white"
            >
                <ArrowLeft class="size-4" /> Back to {{ camp.name }}
            </Link>
            <p
                class="mt-4 text-sm font-semibold tracking-widest text-sky uppercase"
            >
                Camp registration
            </p>
            <h1
                class="font-display text-3xl font-bold tracking-wide uppercase sm:text-4xl"
            >
                {{ camp.name }}
            </h1>
        </div>
    </section>

    <section class="container-site grid gap-10 py-12 md:py-16 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <div
                v-if="!preview && camp.registration_state !== 'open'"
                class="rounded-lg border p-8 text-center"
            >
                <RegistrationStateBadge
                    :state="camp.registration_state"
                    :spots-remaining="camp.spots_remaining"
                />
                <p
                    class="mt-4 font-display text-2xl font-bold text-navy uppercase dark:text-snow"
                >
                    {{
                        camp.registration_state === 'upcoming'
                            ? "Registration hasn't opened yet"
                            : camp.registration_state === 'full'
                              ? 'This camp is full'
                              : 'Registration is closed'
                    }}
                </p>
                <p class="mx-auto mt-2 max-w-md text-muted-foreground">
                    <template
                        v-if="
                            camp.registration_state === 'upcoming' &&
                            camp.registration_opens_at
                        "
                    >
                        Registration opens
                        {{ formatDateTime(camp.registration_opens_at) }}. Check
                        back then!
                    </template>
                    <template v-else
                        >Have a question or want to join the waitlist? Get in
                        touch and we'll help.</template
                    >
                </p>
                <div class="mt-6 flex justify-center gap-3">
                    <Button as-child variant="outline"
                        ><Link :href="camp.url">Camp details</Link></Button
                    >
                    <Button
                        as-child
                        class="bg-sky font-semibold text-navy hover:bg-sky-dark hover:text-white"
                        ><Link :href="contact()">Contact us</Link></Button
                    >
                </div>
            </div>

            <Form
                v-else
                v-bind="CampRegistrationController.store.form(camp.slug)"
                v-slot="{ errors, processing }"
                class="space-y-10"
            >
                <p
                    v-if="preview && camp.registration_state !== 'open'"
                    class="rounded-md border border-amber-300 bg-amber-50 p-4 text-sm text-amber-950"
                >
                    Once published, families would see
                    <strong>{{
                        camp.registration_state === 'upcoming'
                            ? 'registration not open yet'
                            : camp.registration_state === 'full'
                              ? 'this camp is full'
                              : 'registration closed'
                    }}</strong>
                    instead of this form. Check the dates on the Registration
                    tab.
                </p>

                <div
                    v-if="errors.registration"
                    class="rounded-md border border-destructive/40 bg-destructive/10 p-4 text-sm text-destructive"
                    role="alert"
                >
                    {{ errors.registration }}
                </div>

                <p class="text-sm text-muted-foreground">
                    Fields marked <RequiredMark /> are required.
                </p>

                <fieldset class="space-y-5">
                    <legend
                        class="font-display text-2xl font-bold tracking-wide text-navy uppercase dark:text-snow"
                    >
                        Sessions
                    </legend>

                    <div v-if="hasGroups" class="grid content-start gap-2">
                        <p class="text-sm font-medium">
                            Age group <RequiredMark />
                        </p>
                        <div
                            class="flex flex-wrap gap-2"
                            role="radiogroup"
                            aria-label="Age group"
                            aria-required="true"
                        >
                            <label
                                v-for="group in camp.age_groups"
                                :key="group.id"
                                class="flex cursor-pointer items-center gap-2 rounded-md border px-4 py-2 text-sm has-[:checked]:border-sky has-[:checked]:bg-accent"
                            >
                                <input
                                    v-model="ageGroupId"
                                    type="radio"
                                    :value="group.id"
                                    class="accent-sky"
                                />
                                {{ group.name }}
                            </label>
                        </div>
                        <input
                            type="hidden"
                            name="camp_age_group_id"
                            :value="ageGroupId ?? ''"
                        />
                        <InputError
                            :message="
                                errors.camp_age_group_id ??
                                http.errors.camp_age_group_id
                            "
                        />
                    </div>

                    <p class="text-sm font-medium">
                        Choose at least one session or day <RequiredMark />
                    </p>

                    <div
                        v-for="session in camp.sessions"
                        :key="session.id"
                        class="rounded-lg border"
                    >
                        <label
                            class="flex cursor-pointer items-start gap-3 p-4"
                        >
                            <Checkbox
                                class="mt-0.5"
                                :model-value="isWholeSession(session)"
                                @update:model-value="
                                    (checked) =>
                                        toggleSession(session, checked === true)
                                "
                            />
                            <span class="flex-1">
                                <span class="block font-semibold">{{
                                    session.name
                                }}</span>
                                <span
                                    class="block text-sm text-muted-foreground"
                                    >{{
                                        session.days.length > 1
                                            ? `All ${session.days.length} days`
                                            : session.days[0].title
                                    }}
                                    ·
                                    {{
                                        session.price === 0
                                            ? 'Free'
                                            : money(session.price)
                                    }}</span
                                >
                            </span>
                        </label>
                        <ul class="divide-y border-t text-sm">
                            <li
                                v-for="day in session.days"
                                :key="day.id"
                                :class="{ 'opacity-60': isDayFull(day) }"
                            >
                                <!-- The whole row toggles the day when single days can be booked. -->
                                <component
                                    :is="
                                        allowsSingleDays(session)
                                            ? 'label'
                                            : 'div'
                                    "
                                    class="flex items-start gap-3 px-4 py-3"
                                    :class="{
                                        'cursor-pointer':
                                            allowsSingleDays(session) &&
                                            !isDayFull(day),
                                    }"
                                >
                                    <Checkbox
                                        v-if="allowsSingleDays(session)"
                                        class="mt-0.5"
                                        :model-value="
                                            selectedDays.includes(day.id)
                                        "
                                        :disabled="isDayFull(day)"
                                        :aria-label="day.title"
                                        @update:model-value="
                                            (checked) =>
                                                toggleDay(day, checked === true)
                                        "
                                    />
                                    <span class="min-w-0 flex-1">
                                        <span class="block font-medium">{{
                                            day.title
                                        }}</span>
                                        <span
                                            v-if="timeFor(day)"
                                            class="block text-muted-foreground"
                                            >{{ timeFor(day) }}</span
                                        >
                                    </span>
                                    <span
                                        v-if="isDayFull(day)"
                                        class="text-xs font-semibold whitespace-nowrap text-destructive uppercase"
                                        >Full</span
                                    >
                                    <span
                                        v-else-if="allowsSingleDays(session)"
                                        class="whitespace-nowrap text-muted-foreground"
                                        >{{ money(session.day_price) }}</span
                                    >
                                </component>
                            </li>
                        </ul>
                    </div>

                    <p
                        v-if="camp.bundle_price !== null"
                        class="text-sm text-muted-foreground"
                    >
                        Book every session for
                        <strong class="text-foreground">{{
                            money(camp.bundle_price)
                        }}</strong>
                        — it’s applied automatically.
                    </p>

                    <input
                        v-for="id in selectedDays"
                        :key="id"
                        type="hidden"
                        name="days[]"
                        :value="id"
                    />
                    <InputError :message="errors.days ?? http.errors.days" />

                    <div class="grid content-start gap-2 sm:max-w-sm">
                        <Label for="coupon"
                            >Coupon code
                            <span class="text-muted-foreground"
                                >(optional)</span
                            ></Label
                        >
                        <div class="flex gap-2">
                            <Input
                                id="coupon"
                                v-model="couponInput"
                                autocomplete="off"
                                class="uppercase"
                                :disabled="
                                    appliedCode !== '' && !!quote?.coupon_code
                                "
                                @keydown.enter.prevent="applyCoupon"
                            />
                            <Button
                                v-if="appliedCode !== '' && quote?.coupon_code"
                                type="button"
                                variant="outline"
                                @click="removeCoupon"
                                >Remove</Button
                            >
                            <Button
                                v-else
                                type="button"
                                variant="outline"
                                :disabled="couponInput.trim() === ''"
                                @click="applyCoupon"
                                >Apply</Button
                            >
                        </div>
                        <input
                            type="hidden"
                            name="coupon_code"
                            :value="appliedCode"
                        />
                        <p
                            v-if="quote?.coupon_code"
                            class="flex items-center gap-1.5 text-sm text-sky-dark"
                        >
                            <TicketPercent class="size-4" />
                            {{ quote.coupon_code }} applied
                        </p>
                        <p
                            v-else-if="quote?.coupon_note"
                            class="text-sm text-muted-foreground"
                        >
                            {{ quote.coupon_note }}
                        </p>
                        <InputError
                            :message="couponError ?? errors.coupon_code"
                        />
                    </div>
                </fieldset>

                <fieldset class="space-y-5">
                    <legend
                        class="font-display text-2xl font-bold tracking-wide text-navy uppercase dark:text-snow"
                    >
                        Player
                    </legend>
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div class="grid content-start gap-2">
                            <Label for="player_first_name"
                                >First name <RequiredMark
                            /></Label>
                            <Input
                                id="player_first_name"
                                name="player_first_name"
                                required
                                autocomplete="off"
                            />
                            <InputError :message="errors.player_first_name" />
                        </div>
                        <div class="grid content-start gap-2">
                            <Label for="player_last_name"
                                >Last name <RequiredMark
                            /></Label>
                            <Input
                                id="player_last_name"
                                name="player_last_name"
                                required
                                autocomplete="off"
                            />
                            <InputError :message="errors.player_last_name" />
                        </div>
                    </div>
                    <div class="sm:max-w-xs">
                        <DateTimePicker
                            name="player_birthdate"
                            label="Date of birth"
                            :with-time="false"
                            required
                            placeholder="Select birth date"
                            :max="today"
                            :min="earliestBirthdate"
                            :initial-month="defaultBirthMonth"
                            :error="errors.player_birthdate"
                        />
                    </div>
                </fieldset>

                <fieldset class="space-y-5">
                    <legend
                        class="font-display text-2xl font-bold tracking-wide text-navy uppercase dark:text-snow"
                    >
                        Parent / Guardian
                    </legend>
                    <div class="grid content-start gap-2">
                        <Label for="parent_name"
                            >Full name <RequiredMark
                        /></Label>
                        <Input
                            id="parent_name"
                            name="parent_name"
                            required
                            autocomplete="name"
                        />
                        <InputError :message="errors.parent_name" />
                    </div>
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div class="grid content-start gap-2">
                            <Label for="email">Email <RequiredMark /></Label>
                            <Input
                                id="email"
                                name="email"
                                type="email"
                                required
                                autocomplete="email"
                            />
                            <p class="text-xs text-muted-foreground">
                                Your confirmation{{
                                    camp.is_free ? '' : ' and receipt'
                                }}
                                will be sent here.
                            </p>
                            <InputError :message="errors.email" />
                        </div>
                        <div class="grid content-start gap-2">
                            <Label for="phone">Phone <RequiredMark /></Label>
                            <PhoneInput
                                id="phone"
                                name="phone"
                                required
                                autocomplete="tel-national"
                            />
                            <InputError :message="errors.phone" />
                        </div>
                    </div>
                </fieldset>

                <fieldset class="space-y-5">
                    <legend
                        class="font-display text-2xl font-bold tracking-wide text-navy uppercase dark:text-snow"
                    >
                        Emergency & Medical
                    </legend>
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div class="grid content-start gap-2">
                            <Label for="emergency_contact_name"
                                >Emergency contact name <RequiredMark
                            /></Label>
                            <Input
                                id="emergency_contact_name"
                                name="emergency_contact_name"
                                required
                            />
                            <InputError
                                :message="errors.emergency_contact_name"
                            />
                        </div>
                        <div class="grid content-start gap-2">
                            <Label for="emergency_contact_phone"
                                >Emergency contact phone <RequiredMark
                            /></Label>
                            <PhoneInput
                                id="emergency_contact_phone"
                                name="emergency_contact_phone"
                                required
                            />
                            <InputError
                                :message="errors.emergency_contact_phone"
                            />
                        </div>
                    </div>
                    <div class="grid content-start gap-2">
                        <Label for="medical_notes"
                            >Allergies, medical conditions, or notes
                            <span class="text-muted-foreground"
                                >(optional)</span
                            ></Label
                        >
                        <Textarea
                            id="medical_notes"
                            name="medical_notes"
                            rows="3"
                            maxlength="1000"
                        />
                        <InputError :message="errors.medical_notes" />
                    </div>
                </fieldset>

                <!-- Honeypot: hidden from humans, bots tend to fill it. -->
                <div
                    class="absolute top-auto -left-[9999px] h-px w-px overflow-hidden"
                    aria-hidden="true"
                >
                    <label for="website">Website</label>
                    <input
                        id="website"
                        name="website"
                        type="text"
                        tabindex="-1"
                        autocomplete="off"
                    />
                </div>

                <CampRegistrationSummary
                    class="lg:hidden"
                    :camp="camp"
                    :quote="quote"
                    :prompt="summaryPrompt"
                />

                <div
                    class="flex flex-col gap-3 border-t pt-6 sm:flex-row sm:items-center"
                >
                    <Button v-if="preview" type="button" size="lg" disabled>
                        Registration is off in preview
                    </Button>
                    <Button
                        v-else
                        size="lg"
                        :disabled="processing || !canSubmit"
                        class="bg-sky font-semibold text-navy hover:bg-sky-dark hover:text-white"
                    >
                        {{
                            !quote || quote.total === 0
                                ? 'Complete registration'
                                : `Continue to payment · ${money(quote.total)}`
                        }}
                    </Button>
                    <p
                        v-if="quote && quote.total > 0"
                        class="flex items-center gap-1.5 text-xs text-muted-foreground"
                    >
                        <Lock class="size-3.5" /> You'll be redirected to Stripe
                        to complete payment securely.
                    </p>
                </div>
            </Form>
        </div>

        <!-- On large screens the summary sits beside the form; on phones it's shown above the button instead. -->
        <aside class="hidden lg:block">
            <CampRegistrationSummary
                class="sticky top-24"
                :camp="camp"
                :quote="quote"
                :prompt="summaryPrompt"
            />
        </aside>
    </section>
</template>
