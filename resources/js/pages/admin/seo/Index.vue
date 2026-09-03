<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ExternalLink, Pencil } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { edit, index } from '@/routes/admin/seo';

type PageRow = {
    key: string;
    label: string;
    description: string;
    url: string;
    meta: {
        title: string | null;
        description: string | null;
        share_image_url: string | null;
        robots: string | null;
    } | null;
};

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'SEO & Sharing', href: index() }],
    },
});

defineProps<{ pages: PageRow[] }>();
</script>

<template>
    <Head title="SEO & Sharing" />

    <div class="flex flex-col gap-6 p-4 md:p-6">
        <Heading
            title="SEO & Sharing"
            description="Search titles, descriptions, and social share cards for each static page. Teams, Coaches, Camps, Tryouts and Products have their own SEO & Sharing tab on each edit form."
        />

        <div class="rounded-lg border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Page</TableHead>
                        <TableHead class="hidden md:table-cell"
                            >Search title</TableHead
                        >
                        <TableHead class="hidden lg:table-cell"
                            >Share image</TableHead
                        >
                        <TableHead>Status</TableHead>
                        <TableHead class="w-32" />
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="page in pages" :key="page.key">
                        <TableCell>
                            <span class="font-medium">{{ page.label }}</span>
                            <span class="block text-xs text-muted-foreground">{{
                                page.description
                            }}</span>
                        </TableCell>
                        <TableCell
                            class="hidden max-w-xs truncate text-muted-foreground md:table-cell"
                        >
                            {{ page.meta?.title || 'Auto-generated' }}
                        </TableCell>
                        <TableCell class="hidden lg:table-cell">
                            <img
                                v-if="page.meta?.share_image_url"
                                :src="page.meta.share_image_url"
                                alt=""
                                class="h-10 w-20 rounded object-cover"
                            />
                            <span v-else class="text-xs text-muted-foreground"
                                >Default</span
                            >
                        </TableCell>
                        <TableCell>
                            <Badge
                                v-if="page.meta?.robots?.startsWith('noindex')"
                                variant="outline"
                                >Hidden from search</Badge
                            >
                            <Badge v-else-if="page.meta" variant="default"
                                >Customized</Badge
                            >
                            <Badge v-else variant="secondary">Defaults</Badge>
                        </TableCell>
                        <TableCell class="text-right whitespace-nowrap">
                            <Button as-child variant="ghost" size="icon-sm">
                                <a
                                    :href="page.url"
                                    target="_blank"
                                    rel="noopener"
                                    aria-label="View page"
                                    ><ExternalLink class="size-4"
                                /></a>
                            </Button>
                            <Button as-child variant="ghost" size="sm">
                                <Link :href="edit(page.key)"
                                    ><Pencil class="size-4" /> Edit</Link
                                >
                            </Button>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>
    </div>
</template>
