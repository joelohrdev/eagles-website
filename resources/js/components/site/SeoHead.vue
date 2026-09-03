<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import type { SeoProps } from '@/types';

/**
 * Renders <title>, meta description, canonical, robots, Open Graph,
 * Twitter card, and JSON-LD for the current page from the `seo` prop
 * that every public controller passes. Falls back to site defaults.
 */
const page = usePage();

const seo = computed<SeoProps | undefined>(() => page.props.seo);
const siteName = computed(() => seo.value?.site_name ?? page.props.name);

const jsonLd = computed(() =>
    (seo.value?.json_ld ?? []).map((schema) => JSON.stringify(schema)),
);
</script>

<template>
    <Head>
        <title head-key="title">{{ seo?.title ?? siteName }}</title>
        <meta
            v-if="seo?.description"
            head-key="description"
            name="description"
            :content="seo.description"
        />
        <meta
            head-key="robots"
            name="robots"
            :content="seo?.robots ?? 'index,follow'"
        />
        <link
            v-if="seo?.canonical_url"
            head-key="canonical"
            rel="canonical"
            :href="seo.canonical_url"
        />

        <meta
            head-key="og:site_name"
            property="og:site_name"
            :content="siteName"
        />
        <meta head-key="og:locale" property="og:locale" content="en_US" />
        <meta
            head-key="og:type"
            property="og:type"
            :content="seo?.og_type ?? 'website'"
        />
        <meta
            head-key="og:title"
            property="og:title"
            :content="seo?.share_title ?? siteName"
        />
        <meta
            v-if="seo?.share_description"
            head-key="og:description"
            property="og:description"
            :content="seo.share_description"
        />
        <meta
            v-if="seo?.canonical_url"
            head-key="og:url"
            property="og:url"
            :content="seo.canonical_url"
        />
        <template v-if="seo?.share_image_url">
            <meta
                head-key="og:image"
                property="og:image"
                :content="seo.share_image_url"
            />
            <meta
                head-key="og:image:width"
                property="og:image:width"
                content="1200"
            />
            <meta
                head-key="og:image:height"
                property="og:image:height"
                content="630"
            />
            <meta
                v-if="seo.share_image_alt"
                head-key="og:image:alt"
                property="og:image:alt"
                :content="seo.share_image_alt"
            />
        </template>
        <meta
            v-if="seo?.facebook_app_id"
            head-key="fb:app_id"
            property="fb:app_id"
            :content="seo.facebook_app_id"
        />

        <meta
            head-key="twitter:card"
            name="twitter:card"
            :content="seo?.twitter_card ?? 'summary_large_image'"
        />
        <meta
            v-if="seo?.twitter_handle"
            head-key="twitter:site"
            name="twitter:site"
            :content="seo.twitter_handle"
        />
        <meta
            head-key="twitter:title"
            name="twitter:title"
            :content="seo?.share_title ?? siteName"
        />
        <meta
            v-if="seo?.share_description"
            head-key="twitter:description"
            name="twitter:description"
            :content="seo.share_description"
        />
        <meta
            v-if="seo?.share_image_url"
            head-key="twitter:image"
            name="twitter:image"
            :content="seo.share_image_url"
        />

        <!-- eslint-disable vue/no-v-text-v-html-on-component -- JSON-LD must be a <script> tag; content is JSON.stringify output -->
        <component
            :is="'script'"
            v-for="(schema, index) in jsonLd"
            :key="index"
            :head-key="`ld-${index}`"
            type="application/ld+json"
            v-html="schema"
        />
        <!-- eslint-enable vue/no-v-text-v-html-on-component -->
    </Head>
</template>
