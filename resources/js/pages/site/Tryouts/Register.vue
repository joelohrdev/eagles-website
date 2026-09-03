<script setup lang="ts">
import { Form, Link } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import TryoutRegistrationController from '@/actions/App/Http/Controllers/Site/TryoutRegistrationController';
import DateTimePicker from '@/components/DateTimePicker.vue';
import InputError from '@/components/InputError.vue';
import PageHero from '@/components/site/PageHero.vue';
import RegistrationStateBadge from '@/components/site/RegistrationStateBadge.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { formatDateTime } from '@/lib/format';
import { contact } from '@/routes';
import type { Tryout } from '@/types/tryouts';

defineProps<{
    tryout: Tryout;
    positions: string[];
}>();

const today = new Date().toISOString().slice(0, 10);
const earliestBirthdate = `${new Date().getFullYear() - 25}-01-01`;
const defaultBirthMonth = `${new Date().getFullYear() - 12}-01-01`;
</script>

<template>
    <PageHero
        eyebrow="Registration"
        :title="tryout.title"
        :description="`${formatDateTime(tryout.event_at)}${tryout.location ? ` · ${tryout.location}` : ''}`"
        :image-url="tryout.image_url"
    >
        <Button
            as-child
            variant="outline"
            class="border-snow/40 bg-transparent text-snow hover:bg-navy-light hover:text-white"
        >
            <Link :href="tryout.url!"
                ><ArrowLeft class="size-4" /> Back to tryout</Link
            >
        </Button>
    </PageHero>

    <section class="container-site py-12 md:py-16">
        <div
            v-if="tryout.registration_state !== 'open'"
            class="mx-auto max-w-xl rounded-lg border p-8 text-center"
        >
            <RegistrationStateBadge
                :state="tryout.registration_state"
                :spots-remaining="tryout.spots_remaining"
            />
            <h2
                class="mt-4 font-display text-2xl font-bold tracking-wide text-navy uppercase dark:text-snow"
            >
                {{
                    tryout.registration_state === 'upcoming'
                        ? "Registration hasn't opened yet"
                        : tryout.registration_state === 'full'
                          ? 'This tryout is full'
                          : 'Registration is closed'
                }}
            </h2>
            <p class="mt-2 text-muted-foreground">
                <template v-if="tryout.registration_state === 'upcoming'"
                    >Registration opens
                    {{ formatDateTime(tryout.registration_opens_at) }}. Check
                    back then.</template
                >
                <template v-else
                    >Have questions or want to be added to a waitlist? Reach out
                    and we'll help.</template
                >
            </p>
            <Button
                as-child
                class="mt-6 bg-sky font-semibold text-navy hover:bg-sky-dark hover:text-white"
            >
                <Link :href="contact()">Contact us</Link>
            </Button>
        </div>

        <div v-else class="mx-auto max-w-2xl">
            <div class="mb-8">
                <RegistrationStateBadge
                    :state="tryout.registration_state"
                    :spots-remaining="tryout.spots_remaining"
                />
                <p class="mt-3 text-muted-foreground">
                    Registration is free. Fill out the form below and we'll
                    email you a confirmation with what to bring.
                </p>
            </div>

            <Form
                v-bind="TryoutRegistrationController.store.form(tryout.slug)"
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
                        class="font-display text-xl font-bold tracking-wide text-navy uppercase dark:text-snow"
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
                    <div class="grid gap-5 sm:grid-cols-2">
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
                        <div class="grid content-start gap-2">
                            <Label for="primary_position"
                                >Primary position
                                <span class="text-muted-foreground"
                                    >(optional)</span
                                ></Label
                            >
                            <Select name="primary_position">
                                <SelectTrigger id="primary_position"
                                    ><SelectValue
                                        placeholder="Select a position"
                                /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="position in positions"
                                        :key="position"
                                        :value="position"
                                        >{{ position }}</SelectItem
                                    >
                                </SelectContent>
                            </Select>
                            <InputError :message="errors.primary_position" />
                        </div>
                    </div>
                    <div class="grid content-start gap-2">
                        <Label for="current_team"
                            >Current / most recent team
                            <span class="text-muted-foreground"
                                >(optional)</span
                            ></Label
                        >
                        <Input id="current_team" name="current_team" />
                        <InputError :message="errors.current_team" />
                    </div>
                </fieldset>

                <fieldset class="space-y-5">
                    <legend
                        class="font-display text-xl font-bold tracking-wide text-navy uppercase dark:text-snow"
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
                        class="font-display text-xl font-bold tracking-wide text-navy uppercase dark:text-snow"
                    >
                        Additional
                    </legend>
                    <div class="grid content-start gap-2">
                        <Label for="notes"
                            >Anything we should know?
                            <span class="text-muted-foreground"
                                >(optional)</span
                            ></Label
                        >
                        <Textarea
                            id="notes"
                            name="notes"
                            rows="4"
                            maxlength="1000"
                            placeholder="Injuries, scheduling conflicts, questions…"
                        />
                        <InputError :message="errors.notes" />
                    </div>
                </fieldset>

                <div
                    class="absolute -left-[9999px] h-0 w-0 overflow-hidden"
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

                <div class="flex flex-wrap items-center gap-3 border-t pt-6">
                    <Button
                        size="lg"
                        :disabled="processing"
                        class="bg-sky font-semibold text-navy hover:bg-sky-dark hover:text-white"
                    >
                        {{
                            processing ? 'Submitting…' : 'Complete registration'
                        }}
                    </Button>
                    <Button as-child variant="ghost">
                        <Link :href="tryout.url!">Cancel</Link>
                    </Button>
                </div>
            </Form>
        </div>
    </section>
</template>
