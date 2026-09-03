<script setup lang="ts">
import { Form, Link } from '@inertiajs/vue3';
import { CheckCircle2, Mail, MapPin, Phone } from '@lucide/vue';
import { computed } from 'vue';
import ContactController from '@/actions/App/Http/Controllers/Site/ContactController';
import InputError from '@/components/InputError.vue';
import PageHero from '@/components/site/PageHero.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { contact } from '@/routes';

const props = defineProps<{
    intro: string | null;
    org: {
        org_name: string;
        phone: string | null;
        email: string | null;
        address_line1: string | null;
        address_city: string | null;
        address_state: string | null;
        address_postal_code: string | null;
    };
    sent: boolean;
}>();

const address = computed(() =>
    [
        props.org.address_line1,
        [props.org.address_city, props.org.address_state]
            .filter(Boolean)
            .join(', '),
        props.org.address_postal_code,
    ]
        .filter(Boolean)
        .join(' '),
);
</script>

<template>
    <div>
        <PageHero
            title="Contact Us"
            eyebrow="Get in touch"
            :description="intro"
        />

        <section
            class="container-site grid gap-10 py-12 md:grid-cols-5 md:py-16"
        >
            <div class="md:col-span-3">
                <div
                    v-if="sent"
                    class="rounded-lg border border-sky/40 bg-accent p-8 text-center"
                >
                    <CheckCircle2 class="mx-auto size-12 text-sky" />
                    <h2
                        class="mt-4 font-display text-2xl font-bold tracking-wide uppercase"
                    >
                        Message sent
                    </h2>
                    <p class="mt-2 text-muted-foreground">
                        Thanks for reaching out — we'll get back to you as soon
                        as we can.
                    </p>
                    <Button as-child variant="outline" class="mt-6">
                        <Link :href="contact()">Send another message</Link>
                    </Button>
                </div>

                <Form
                    v-else
                    v-bind="ContactController.store.form()"
                    class="space-y-5 rounded-lg border bg-card p-6 shadow-sm md:p-8"
                    v-slot="{ errors, processing }"
                >
                    <h2
                        class="font-display text-2xl font-semibold tracking-wide uppercase"
                    >
                        Send us a message
                    </h2>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div class="grid content-start gap-2">
                            <Label for="name">Name</Label>
                            <Input
                                id="name"
                                name="name"
                                required
                                autocomplete="name"
                                placeholder="Your name"
                            />
                            <InputError :message="errors.name" />
                        </div>
                        <div class="grid content-start gap-2">
                            <Label for="email">Email</Label>
                            <Input
                                id="email"
                                name="email"
                                type="email"
                                required
                                autocomplete="email"
                                placeholder="you@example.com"
                            />
                            <InputError :message="errors.email" />
                        </div>
                        <div class="grid content-start gap-2">
                            <Label for="phone"
                                >Phone
                                <span class="text-muted-foreground"
                                    >(optional)</span
                                ></Label
                            >
                            <Input
                                id="phone"
                                name="phone"
                                type="tel"
                                autocomplete="tel"
                                placeholder="(555) 555-5555"
                            />
                            <InputError :message="errors.phone" />
                        </div>
                        <div class="grid content-start gap-2">
                            <Label for="subject"
                                >Subject
                                <span class="text-muted-foreground"
                                    >(optional)</span
                                ></Label
                            >
                            <Input
                                id="subject"
                                name="subject"
                                placeholder="Tryouts, camps, teams…"
                            />
                            <InputError :message="errors.subject" />
                        </div>
                    </div>

                    <div class="grid content-start gap-2">
                        <Label for="message">Message</Label>
                        <Textarea
                            id="message"
                            name="message"
                            rows="6"
                            required
                            placeholder="How can we help?"
                        />
                        <InputError :message="errors.message" />
                    </div>

                    <div class="hidden" aria-hidden="true">
                        <label for="website">Website</label>
                        <input
                            id="website"
                            name="website"
                            type="text"
                            tabindex="-1"
                            autocomplete="off"
                        />
                    </div>

                    <Button
                        :disabled="processing"
                        class="bg-sky font-semibold text-navy hover:bg-sky-dark hover:text-white"
                    >
                        Send message
                    </Button>
                </Form>
            </div>

            <aside class="md:col-span-2">
                <div class="rounded-lg bg-navy p-6 text-snow md:p-8">
                    <h2
                        class="font-display text-2xl font-semibold tracking-wide uppercase"
                    >
                        {{ org.org_name }}
                    </h2>
                    <ul class="mt-6 space-y-4 text-sm">
                        <li v-if="org.phone" class="flex items-start gap-3">
                            <Phone class="mt-0.5 size-5 shrink-0 text-sky" />
                            <div>
                                <p class="font-medium">Phone</p>
                                <a
                                    :href="`tel:${org.phone.replace(/[^0-9+]/g, '')}`"
                                    class="text-stone hover:text-white"
                                    >{{ org.phone }}</a
                                >
                            </div>
                        </li>
                        <li v-if="org.email" class="flex items-start gap-3">
                            <Mail class="mt-0.5 size-5 shrink-0 text-sky" />
                            <div>
                                <p class="font-medium">Email</p>
                                <a
                                    :href="`mailto:${org.email}`"
                                    class="break-all text-stone hover:text-white"
                                    >{{ org.email }}</a
                                >
                            </div>
                        </li>
                        <li v-if="address" class="flex items-start gap-3">
                            <MapPin class="mt-0.5 size-5 shrink-0 text-sky" />
                            <div>
                                <p class="font-medium">Location</p>
                                <p class="text-stone">{{ address }}</p>
                            </div>
                        </li>
                    </ul>
                    <p class="mt-6 text-xs text-stone">
                        We typically respond within one business day.
                    </p>
                </div>
            </aside>
        </section>
    </div>
</template>
