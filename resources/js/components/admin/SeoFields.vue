<script setup lang="ts">
import { computed, ref } from 'vue';
import ImageUpload from '@/components/admin/ImageUpload.vue';
import InputError from '@/components/InputError.vue';
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
import { shareCard } from '@/routes';
import type { SeoMetaForm } from '@/types';

/**
 * "SEO & Sharing" form section. Drop inside an Inertia <Form>; fields submit
 * as `seo[...]` and the share image as `seo_share_image`. Shows live Google
 * result and social card previews using fallbacks when fields are blank.
 */
const props = withDefaults(
    defineProps<{
        seo?: SeoMetaForm | null;
        errors?: Record<string, string>;
        /** Fallback values used when the field is blank (what the site will auto-generate). */
        fallback: {
            title: string;
            description?: string | null;
            image_url?: string | null;
            url?: string;
        };
        siteName?: string;
    }>(),
    {
        seo: null,
        errors: () => ({}),
        siteName: 'Eagles Baseball Travel',
    },
);

const title = ref(props.seo?.title ?? '');
const description = ref(props.seo?.description ?? '');
const shareTitle = ref(props.seo?.share_title ?? '');
const shareDescription = ref(props.seo?.share_description ?? '');
const shareImageAlt = ref(props.seo?.share_image_alt ?? '');
const canonical = ref(props.seo?.canonical_url ?? '');
const robots = ref(props.seo?.robots ?? 'index,follow');
const twitterCard = ref(props.seo?.twitter_card ?? 'summary_large_image');

const previewTitle = computed(() => title.value || props.fallback.title);
const previewDescription = computed(
    () => description.value || props.fallback.description || '',
);
const previewShareTitle = computed(
    () => shareTitle.value || title.value || props.fallback.title,
);
const previewShareDescription = computed(
    () =>
        shareDescription.value ||
        description.value ||
        props.fallback.description ||
        '',
);
/**
 * Mirrors the server's fallback chain: this page's image, then the page's own
 * fallback, then the site default share card served at /share-card.png.
 */
const previewShareImage = computed(
    () =>
        props.seo?.share_image_url ||
        props.fallback.image_url ||
        shareCard.url(),
);

const previewUrl = computed(() => {
    try {
        return new URL(
            props.fallback.url ??
                (typeof window !== 'undefined'
                    ? window.location.href
                    : 'https://example.com'),
        ).host;
    } catch {
        return '';
    }
});
</script>

<template>
    <div class="grid gap-6 lg:grid-cols-2">
        <div class="space-y-5">
            <div>
                <h3 class="text-sm font-semibold">Search engines</h3>
                <p class="text-xs text-muted-foreground">
                    Leave blank to use the automatically generated values shown
                    in the preview.
                </p>
            </div>

            <div class="grid content-start gap-2">
                <Label for="seo_title">Page title</Label>
                <Input
                    id="seo_title"
                    v-model="title"
                    name="seo[title]"
                    maxlength="70"
                    :placeholder="fallback.title"
                />
                <p class="text-xs text-muted-foreground">
                    {{ title.length }}/70 characters
                </p>
                <InputError :message="errors['seo.title']" />
            </div>

            <div class="grid content-start gap-2">
                <Label for="seo_description">Meta description</Label>
                <Textarea
                    id="seo_description"
                    v-model="description"
                    name="seo[description]"
                    rows="3"
                    maxlength="320"
                    :placeholder="
                        fallback.description ??
                        'A short summary shown in search results.'
                    "
                />
                <p class="text-xs text-muted-foreground">
                    {{ description.length }}/320 characters (aim for 120–160)
                </p>
                <InputError :message="errors['seo.description']" />
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid content-start gap-2">
                    <Label for="seo_robots">Indexing</Label>
                    <Select v-model="robots" name="seo[robots]">
                        <SelectTrigger id="seo_robots"
                            ><SelectValue
                        /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="index,follow"
                                >Index (default)</SelectItem
                            >
                            <SelectItem value="noindex,follow"
                                >Hide from search (noindex)</SelectItem
                            >
                            <SelectItem value="noindex,nofollow"
                                >Hide and don't follow links</SelectItem
                            >
                        </SelectContent>
                    </Select>
                    <InputError :message="errors['seo.robots']" />
                </div>
                <div class="grid content-start gap-2">
                    <Label for="seo_canonical">Canonical URL</Label>
                    <Input
                        id="seo_canonical"
                        v-model="canonical"
                        name="seo[canonical_url]"
                        type="url"
                        placeholder="Leave blank for this page's URL"
                    />
                    <InputError :message="errors['seo.canonical_url']" />
                </div>
            </div>

            <div class="border-t pt-5">
                <h3 class="text-sm font-semibold">Social sharing</h3>
                <p class="text-xs text-muted-foreground">
                    What people see when this page is shared in texts, Facebook,
                    or group chats.
                </p>
            </div>

            <div class="grid content-start gap-2">
                <Label for="seo_share_title">Share title</Label>
                <Input
                    id="seo_share_title"
                    v-model="shareTitle"
                    name="seo[share_title]"
                    maxlength="95"
                    :placeholder="previewTitle"
                />
                <InputError :message="errors['seo.share_title']" />
            </div>

            <div class="grid content-start gap-2">
                <Label for="seo_share_description">Share description</Label>
                <Textarea
                    id="seo_share_description"
                    v-model="shareDescription"
                    name="seo[share_description]"
                    rows="2"
                    maxlength="320"
                    :placeholder="
                        previewDescription ||
                        'Short, punchy summary for the share card.'
                    "
                />
                <InputError :message="errors['seo.share_description']" />
            </div>

            <ImageUpload
                name="seo_share_image"
                label="Share image"
                aspect="share"
                remove-name="seo[remove_share_image]"
                :current-url="seo?.share_image_url ?? null"
                :error="errors['seo_share_image']"
                hint="Cropped to 1200×630. Minimum 600×315. Falls back to the page image, then the site default."
            />

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid content-start gap-2">
                    <Label for="seo_share_image_alt"
                        >Share image alt text</Label
                    >
                    <Input
                        id="seo_share_image_alt"
                        v-model="shareImageAlt"
                        name="seo[share_image_alt]"
                        placeholder="Describe the image"
                    />
                    <InputError :message="errors['seo.share_image_alt']" />
                </div>
                <div class="grid content-start gap-2">
                    <Label for="seo_twitter_card">X / Twitter card</Label>
                    <Select v-model="twitterCard" name="seo[twitter_card]">
                        <SelectTrigger id="seo_twitter_card"
                            ><SelectValue
                        /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="summary_large_image"
                                >Large image</SelectItem
                            >
                            <SelectItem value="summary"
                                >Small summary</SelectItem
                            >
                        </SelectContent>
                    </Select>
                </div>
            </div>
        </div>

        <div class="space-y-6 lg:sticky lg:top-6 lg:self-start">
            <div>
                <p
                    class="mb-2 text-xs font-medium tracking-wide text-muted-foreground uppercase"
                >
                    Google preview
                </p>
                <div class="rounded-lg border bg-card p-4">
                    <p class="text-xs text-muted-foreground">
                        {{ previewUrl }}
                    </p>
                    <p
                        class="mt-1 truncate text-lg text-[#1a0dab] dark:text-sky"
                    >
                        {{ previewTitle }} | {{ siteName }}
                    </p>
                    <p class="mt-1 line-clamp-2 text-sm text-muted-foreground">
                        {{
                            previewDescription ||
                            'No description yet — add one above.'
                        }}
                    </p>
                </div>
            </div>

            <div>
                <p
                    class="mb-2 text-xs font-medium tracking-wide text-muted-foreground uppercase"
                >
                    Share card preview (Facebook / iMessage)
                </p>
                <div class="max-w-md overflow-hidden rounded-lg border bg-card">
                    <div class="aspect-[1200/630] bg-muted">
                        <img
                            :src="previewShareImage"
                            :alt="shareImageAlt"
                            class="size-full object-cover"
                        />
                    </div>
                    <div class="border-t bg-secondary/40 p-3">
                        <p
                            class="text-[11px] tracking-wide text-muted-foreground uppercase"
                        >
                            {{ previewUrl }}
                        </p>
                        <p class="truncate font-semibold">
                            {{ previewShareTitle }}
                        </p>
                        <p class="line-clamp-2 text-sm text-muted-foreground">
                            {{ previewShareDescription }}
                        </p>
                    </div>
                </div>
                <p class="mt-2 text-xs text-muted-foreground">
                    Changed the image? Facebook caches cards — use the
                    <a
                        href="https://developers.facebook.com/tools/debug/"
                        target="_blank"
                        rel="noopener"
                        class="underline"
                        >Sharing Debugger</a
                    >
                    to re-scrape.
                </p>
            </div>
        </div>
    </div>
</template>
