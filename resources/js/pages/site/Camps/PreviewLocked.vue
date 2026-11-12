<script setup lang="ts">
import { Form, Link } from '@inertiajs/vue3';
import { LockKeyhole } from '@lucide/vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

/**
 * Shown instead of a camp preview to visitors who aren't signed in: enter the
 * camp's 4-digit passcode, or log in as staff.
 */
defineProps<{
    camp: { name: string; slug: string };
    unlockUrl: string;
    /** Which preview page to open after unlocking. */
    to: string;
    loginUrl: string;
}>();
</script>

<template>
    <section class="container-site flex justify-center py-16 md:py-24">
        <div class="w-full max-w-sm rounded-lg border bg-card p-8 shadow-sm">
            <div
                class="mx-auto flex size-12 items-center justify-center rounded-full bg-accent text-sky-dark"
            >
                <LockKeyhole class="size-6" />
            </div>
            <p
                class="mt-4 text-center text-xs font-semibold tracking-widest text-sky uppercase"
            >
                Camp preview
            </p>
            <h1
                class="mt-1 text-center font-display text-3xl font-bold tracking-wide text-navy uppercase dark:text-snow"
            >
                {{ camp.name }}
            </h1>
            <p class="mt-2 text-center text-sm text-muted-foreground">
                Enter the 4-digit passcode you were given to see this camp
                before it goes live.
            </p>

            <Form
                :action="unlockUrl"
                method="post"
                v-slot="{ errors, processing }"
                class="mt-6 space-y-4"
            >
                <input type="hidden" name="to" :value="to" />
                <div class="grid gap-2">
                    <Label for="passcode" class="sr-only">Passcode</Label>
                    <Input
                        id="passcode"
                        name="passcode"
                        type="text"
                        inputmode="numeric"
                        pattern="[0-9]{4}"
                        maxlength="4"
                        autocomplete="one-time-code"
                        autofocus
                        required
                        placeholder="••••"
                        class="h-12 text-center font-mono text-2xl tracking-[0.5em]"
                        :aria-invalid="!!errors.passcode || undefined"
                    />
                    <InputError
                        :message="errors.passcode"
                        class="text-center"
                    />
                </div>
                <Button
                    type="submit"
                    size="lg"
                    class="w-full bg-sky font-semibold text-navy hover:bg-sky-dark hover:text-white"
                    :disabled="processing"
                >
                    View preview
                </Button>
            </Form>

            <p class="mt-6 text-center text-sm text-muted-foreground">
                Eagles staff?
                <Link
                    :href="loginUrl"
                    class="font-semibold text-sky-dark hover:underline"
                    >Log in instead</Link
                >
            </p>
        </div>
    </section>
</template>
