<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { InertiaLinkProps } from '@inertiajs/vue3';
import { Trash2 } from '@lucide/vue';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/components/ui/alert-dialog';
import { Button } from '@/components/ui/button';

withDefaults(
    defineProps<{
        href: NonNullable<InertiaLinkProps['href']>;
        title?: string;
        description?: string;
        label?: string;
        size?: 'default' | 'sm' | 'icon' | 'icon-sm';
    }>(),
    {
        title: 'Delete this item?',
        description: 'This action cannot be undone.',
        label: 'Delete',
        size: 'sm',
    },
);
</script>

<template>
    <AlertDialog>
        <AlertDialogTrigger as-child>
            <Button
                variant="ghost"
                :size="size"
                class="text-destructive hover:text-destructive"
            >
                <Trash2 class="size-4" />
                <span v-if="!size.startsWith('icon')">{{ label }}</span>
                <span v-else class="sr-only">{{ label }}</span>
            </Button>
        </AlertDialogTrigger>
        <AlertDialogContent>
            <AlertDialogHeader>
                <AlertDialogTitle>{{ title }}</AlertDialogTitle>
                <AlertDialogDescription>{{
                    description
                }}</AlertDialogDescription>
            </AlertDialogHeader>
            <AlertDialogFooter>
                <AlertDialogCancel>Cancel</AlertDialogCancel>
                <AlertDialogAction as-child>
                    <Link
                        :href="href"
                        method="delete"
                        as="button"
                        class="bg-destructive text-white hover:bg-destructive/90"
                    >
                        {{ label }}
                    </Link>
                </AlertDialogAction>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>
</template>
