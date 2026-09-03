<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { home, login } from '@/routes';

defineProps<{
    accepted: boolean;
}>();

defineOptions({
    layout: {
        title: 'Invitation unavailable',
        description: 'This invitation link can no longer be used',
    },
});
</script>

<template>
    <Head title="Invitation unavailable" />

    <div class="flex flex-col gap-4 text-center text-sm text-muted-foreground">
        <p v-if="accepted">
            This invitation has already been accepted. If that was you, sign in
            below.
        </p>
        <p v-else>
            This invitation has expired. Ask an admin to send you a new one.
        </p>
        <div class="flex justify-center gap-2">
            <Button as-child variant="outline"
                ><Link :href="home()">Back to site</Link></Button
            >
            <Button v-if="accepted" as-child
                ><Link :href="login()">Sign in</Link></Button
            >
        </div>
    </div>
</template>
