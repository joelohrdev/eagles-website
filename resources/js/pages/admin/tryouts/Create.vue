<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import TryoutController from '@/actions/App/Http/Controllers/Admin/TryoutController';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { create, index } from '@/routes/admin/tryouts';
import TryoutForm from './TryoutForm.vue';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Tryouts', href: index() },
            { title: 'New tryout', href: create() },
        ],
    },
});
</script>

<template>
    <Head title="New tryout" />

    <div class="p-6">
        <Heading
            title="New tryout"
            description="Schedule a tryout and open registration."
        />

        <Form
            v-bind="TryoutController.store.form()"
            v-slot="{ errors, processing }"
            class="space-y-6"
        >
            <TryoutForm :errors="errors" />

            <div class="flex items-center gap-3 border-t pt-6">
                <Button :disabled="processing">Create tryout</Button>
                <Button as-child variant="ghost">
                    <Link :href="index()">Cancel</Link>
                </Button>
            </div>
        </Form>
    </div>
</template>
