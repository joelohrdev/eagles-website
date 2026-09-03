<script setup lang="ts">
import { Form, Link } from '@inertiajs/vue3';
import { ArrowLeft, CalendarDays, Lock, MapPin } from '@lucide/vue';
import CampRegistrationController from '@/actions/App/Http/Controllers/Site/CampRegistrationController';
import DateTimePicker from '@/components/DateTimePicker.vue';
import InputError from '@/components/InputError.vue';
import RegistrationStateBadge from '@/components/site/RegistrationStateBadge.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { formatDateTime, money } from '@/lib/format';
import { contact } from '@/routes';
import type { CampDetail } from '@/types/camps';

defineProps<{
    camp: CampDetail;
}>();

const today = new Date().toISOString().slice(0, 10);
const earliestBirthdate = `${new Date().getFullYear() - 25}-01-01`;
const defaultBirthMonth = `${new Date().getFullYear() - 12}-01-01`;
</script>

<template>
    <section class="bg-navy text-snow">
        <div class="container-site py-10 md:py-12">
            <Link
                :href="camp.url"
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
                v-if="camp.registration_state !== 'open'"
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
                <div
                    v-if="errors.registration"
                    class="rounded-md border border-destructive/40 bg-destructive/10 p-4 text-sm text-destructive"
                    role="alert"
                >
                    {{ errors.registration }}
                </div>

                <fieldset class="space-y-5">
                    <legend
                        class="font-display text-2xl font-bold tracking-wide text-navy uppercase dark:text-snow"
                    >
                        Player
                    </legend>
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div class="grid content-start gap-2">
                            <Label for="player_first_name">First name</Label>
                            <Input
                                id="player_first_name"
                                name="player_first_name"
                                required
                                autocomplete="off"
                            />
                            <InputError :message="errors.player_first_name" />
                        </div>
                        <div class="grid content-start gap-2">
                            <Label for="player_last_name">Last name</Label>
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
                        <Label for="parent_name">Full name</Label>
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
                            <Label for="email">Email</Label>
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
                            <Label for="phone">Phone</Label>
                            <Input
                                id="phone"
                                name="phone"
                                type="tel"
                                required
                                autocomplete="tel"
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
                                >Emergency contact name</Label
                            >
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
                                >Emergency contact phone</Label
                            >
                            <Input
                                id="emergency_contact_phone"
                                name="emergency_contact_phone"
                                type="tel"
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

                <div
                    class="flex flex-col gap-3 border-t pt-6 sm:flex-row sm:items-center"
                >
                    <Button
                        size="lg"
                        :disabled="processing"
                        class="bg-sky font-semibold text-navy hover:bg-sky-dark hover:text-white"
                    >
                        {{
                            camp.is_free
                                ? 'Complete registration'
                                : `Continue to payment · ${money(camp.price)}`
                        }}
                    </Button>
                    <p
                        v-if="!camp.is_free"
                        class="flex items-center gap-1.5 text-xs text-muted-foreground"
                    >
                        <Lock class="size-3.5" /> You'll be redirected to Stripe
                        to complete payment securely.
                    </p>
                </div>
            </Form>
        </div>

        <aside>
            <div class="sticky top-24 rounded-lg border bg-card p-6 shadow-sm">
                <p
                    class="text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                >
                    Summary
                </p>
                <p
                    class="mt-2 font-display text-2xl font-bold tracking-wide text-navy uppercase dark:text-snow"
                >
                    {{ camp.name }}
                </p>
                <ul class="mt-3 space-y-1.5 text-sm text-muted-foreground">
                    <li class="flex items-center gap-2">
                        <CalendarDays class="size-4 shrink-0 text-sky" />
                        {{ formatDateTime(camp.starts_at) }}
                    </li>
                    <li v-if="camp.location" class="flex items-center gap-2">
                        <MapPin class="size-4 shrink-0 text-sky" />
                        {{ camp.location }}
                    </li>
                </ul>
                <div
                    class="mt-4 flex items-baseline justify-between border-t pt-4"
                >
                    <span class="text-sm text-muted-foreground">Total</span>
                    <span
                        class="font-display text-3xl font-bold text-navy dark:text-snow"
                        >{{ camp.is_free ? 'Free' : money(camp.price) }}</span
                    >
                </div>
                <p
                    v-if="!camp.is_free"
                    class="mt-3 text-xs text-muted-foreground"
                >
                    Your spot is held for 30 minutes while you complete payment.
                </p>
            </div>
        </aside>
    </section>
</template>
