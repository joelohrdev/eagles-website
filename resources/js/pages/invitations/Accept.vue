<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InvitationAcceptController from '@/actions/App/Http/Controllers/Site/InvitationAcceptController';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

const props = defineProps<{
    token: string;
    email: string;
    role: string;
}>();

defineOptions({
    layout: {
        title: 'Accept your invitation',
        description: 'Create your account to start managing the site',
    },
});
</script>

<template>
    <Head title="Accept invitation" />

    <p class="mb-6 rounded-md border bg-muted/50 px-3 py-2 text-center text-sm">
        You've been invited as <strong>{{ role }}</strong> for
        <strong>{{ email }}</strong
        >.
    </p>

    <Form
        v-bind="InvitationAcceptController.store.form(props.token)"
        :reset-on-success="['password', 'password_confirmation']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-6"
    >
        <div class="grid gap-6">
            <div class="grid content-start gap-2">
                <Label for="name">Your name</Label>
                <Input
                    id="name"
                    name="name"
                    required
                    autofocus
                    autocomplete="name"
                    placeholder="Full name"
                />
                <InputError :message="errors.name" />
            </div>

            <div class="grid content-start gap-2">
                <Label for="email">Email address</Label>
                <Input id="email" type="email" :model-value="email" disabled />
            </div>

            <div class="grid content-start gap-2">
                <Label for="password">Password</Label>
                <PasswordInput
                    id="password"
                    name="password"
                    required
                    autocomplete="new-password"
                    placeholder="Password"
                />
                <InputError :message="errors.password" />
            </div>

            <div class="grid content-start gap-2">
                <Label for="password_confirmation">Confirm password</Label>
                <PasswordInput
                    id="password_confirmation"
                    name="password_confirmation"
                    required
                    autocomplete="new-password"
                    placeholder="Confirm password"
                />
                <InputError :message="errors.password_confirmation" />
            </div>

            <Button type="submit" class="mt-2 w-full" :disabled="processing">
                <Spinner v-if="processing" />
                Create account
            </Button>
        </div>
    </Form>
</template>
