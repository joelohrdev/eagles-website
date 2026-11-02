<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { Check, Copy, ExternalLink } from '@lucide/vue';
import { ref } from 'vue';
import PaymentSettingController from '@/actions/App/Http/Controllers/Admin/PaymentSettingController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { edit as paymentsEdit } from '@/routes/admin/payments';

type Credential = {
    masked: string | null;
    source: 'dashboard' | 'env' | null;
};

defineProps<{
    stripe: {
        secretKey: Credential;
        webhookSecret: Credential;
        testMode: boolean;
    };
    webhookUrl: string;
    webhookEvents: string[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Payments', href: paymentsEdit() }],
    },
});

const copied = ref<string | null>(null);

async function copy(value: string) {
    try {
        await navigator.clipboard.writeText(value);
        copied.value = value;
        setTimeout(() => (copied.value = null), 2000);
    } catch {
        // clipboard unavailable
    }
}

const sourceLabel = (credential: Credential): string =>
    credential.source === 'dashboard'
        ? 'Saved on this page'
        : credential.source === 'env'
          ? 'From the server’s .env file'
          : 'Not set';
</script>

<template>
    <Head title="Payments" />

    <div class="flex max-w-3xl flex-col gap-6 p-4 md:p-6">
        <Heading
            title="Payments"
            description="Connect Stripe so families can pay for camps and merch. It takes two things from Stripe: a key and a webhook signing secret."
        />

        <Card>
            <CardHeader>
                <CardTitle class="flex flex-wrap items-center gap-2">
                    Current connection
                    <Badge
                        v-if="stripe.secretKey.masked"
                        :variant="stripe.testMode ? 'secondary' : 'default'"
                    >
                        {{ stripe.testMode ? 'Test mode' : 'Live' }}
                    </Badge>
                </CardTitle>
            </CardHeader>
            <CardContent>
                <dl class="grid gap-3 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="text-muted-foreground">Stripe key</dt>
                        <dd class="font-mono">
                            {{ stripe.secretKey.masked ?? '—' }}
                        </dd>
                        <dd class="text-xs text-muted-foreground">
                            {{ sourceLabel(stripe.secretKey) }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-muted-foreground">
                            Webhook signing secret
                        </dt>
                        <dd class="font-mono">
                            {{ stripe.webhookSecret.masked ?? '—' }}
                        </dd>
                        <dd class="text-xs text-muted-foreground">
                            {{ sourceLabel(stripe.webhookSecret) }}
                        </dd>
                    </div>
                </dl>
                <p
                    v-if="!stripe.secretKey.masked"
                    class="mt-4 text-sm text-muted-foreground"
                >
                    Until a key is saved, checkout runs in demo mode and no
                    money is collected.
                </p>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle>Step 1: Create a restricted key</CardTitle>
                <CardDescription>
                    A restricted key can only start checkouts. If it ever leaks,
                    nobody can use it to read customer data or move money.
                </CardDescription>
            </CardHeader>
            <CardContent class="space-y-4 text-sm">
                <Button as-child>
                    <a
                        href="https://dashboard.stripe.com/apikeys/create"
                        target="_blank"
                        rel="noopener"
                    >
                        Open Stripe’s new key page
                        <ExternalLink class="size-4" />
                    </a>
                </Button>
                <ol class="list-decimal space-y-2 pl-5">
                    <li>
                        Check that Stripe is in your live account, not a
                        sandbox. Sandbox keys only accept test cards.
                    </li>
                    <li>
                        In <strong>Key name</strong>, type
                        <code>Eagles website</code>.
                    </li>
                    <li>
                        In the <strong>Filter resources</strong> box, type
                        <code>Checkout Sessions</code>. On that row, click
                        <strong>Write</strong>.
                    </li>
                    <li>
                        Leave every other row on <strong>None</strong>, which is
                        the default.
                    </li>
                    <li>
                        Scroll to the bottom, click
                        <strong>Create key</strong>, and enter the code Stripe
                        texts or emails you.
                    </li>
                    <li>
                        Click the key to copy it. It starts with
                        <code>rk_live_</code>. Stripe shows it once, so paste it
                        into Step 3 before you close the window.
                    </li>
                </ol>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle>Step 2: Add the webhook</CardTitle>
                <CardDescription>
                    Stripe uses the webhook to tell this site when someone pays,
                    abandons checkout, or gets a full refund. Without it, paid
                    orders stay marked as pending.
                </CardDescription>
            </CardHeader>
            <CardContent class="space-y-4 text-sm">
                <Button as-child>
                    <a
                        href="https://dashboard.stripe.com/webhooks"
                        target="_blank"
                        rel="noopener"
                    >
                        Open Stripe webhooks
                        <ExternalLink class="size-4" />
                    </a>
                </Button>
                <ol class="list-decimal space-y-2 pl-5">
                    <li>Click <strong>Create an event destination</strong>.</li>
                    <li>Choose <strong>Your account</strong>.</li>
                    <li>
                        For the payload format, choose
                        <strong>Snapshot</strong>. Stripe labels Thin as
                        recommended, but this site reads the order details out
                        of each event, and only Snapshot events include them.
                        Leave the API version as it is.
                    </li>
                    <li>
                        Search for and tick these three events:
                        <ul class="mt-2 space-y-1">
                            <li
                                v-for="event in webhookEvents"
                                :key="event"
                                class="flex items-center gap-2"
                            >
                                <code class="rounded bg-muted px-1.5 py-0.5">{{
                                    event
                                }}</code>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    @click="copy(event)"
                                >
                                    <Check
                                        v-if="copied === event"
                                        class="size-4"
                                    />
                                    <Copy v-else class="size-4" />
                                    <span class="sr-only">Copy</span>
                                </Button>
                            </li>
                        </ul>
                    </li>
                    <li>
                        Click <strong>Continue</strong>, choose
                        <strong>Webhook endpoint</strong>, and click
                        <strong>Continue</strong> again.
                    </li>
                    <li>
                        Paste this into <strong>Endpoint URL</strong>, then
                        create the destination:
                        <div class="mt-2 flex items-center gap-2">
                            <Input
                                :model-value="webhookUrl"
                                readonly
                                class="font-mono"
                                aria-label="Webhook URL"
                            />
                            <Button
                                type="button"
                                variant="outline"
                                @click="copy(webhookUrl)"
                            >
                                <Check
                                    v-if="copied === webhookUrl"
                                    class="size-4"
                                />
                                <Copy v-else class="size-4" />
                                {{ copied === webhookUrl ? 'Copied' : 'Copy' }}
                            </Button>
                        </div>
                    </li>
                    <li>
                        On the destination’s page, click
                        <strong>Reveal secret</strong> and copy the signing
                        secret. It starts with <code>whsec_</code>.
                    </li>
                </ol>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle>Step 3: Paste both here</CardTitle>
                <CardDescription>
                    Both are stored encrypted. Leave a field blank to keep the
                    value already saved.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <Form
                    v-bind="PaymentSettingController.update.form()"
                    reset-on-success
                    class="space-y-4"
                    v-slot="{ errors, processing }"
                >
                    <div class="grid gap-2">
                        <Label for="secret_key">Restricted key</Label>
                        <Input
                            id="secret_key"
                            name="secret_key"
                            type="password"
                            autocomplete="off"
                            placeholder="rk_live_…"
                            class="font-mono"
                        />
                        <InputError :message="errors.secret_key" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="webhook_secret"
                            >Webhook signing secret</Label
                        >
                        <Input
                            id="webhook_secret"
                            name="webhook_secret"
                            type="password"
                            autocomplete="off"
                            placeholder="whsec_…"
                            class="font-mono"
                        />
                        <InputError :message="errors.webhook_secret" />
                    </div>
                    <Button type="submit" :disabled="processing">
                        Save keys
                    </Button>
                </Form>
            </CardContent>
        </Card>

        <div class="space-y-2 text-sm text-muted-foreground">
            <p>
                <strong class="text-foreground">Testing first?</strong>
                Switch Stripe to a sandbox and follow the same steps. You’ll get
                a key starting with <code>rk_test_</code> and a separate
                <code>whsec_</code> secret. Pay with card
                <code>4242 4242 4242 4242</code>, any future date, and any CVC.
                Repeat both steps in live mode before taking real payments.
            </p>
            <p>
                <strong class="text-foreground">Checkout failing?</strong> In
                Stripe’s API keys list, open the ⋯ menu on the Eagles website
                key and choose <strong>View request logs</strong>. A failed
                request names the permission the key is missing. Add it under ⋯
                → <strong>Edit key</strong>.
            </p>
        </div>
    </div>
</template>
