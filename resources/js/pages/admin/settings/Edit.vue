<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { Plus, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import SiteSettingController from '@/actions/App/Http/Controllers/Admin/SiteSettingController';
import FormTabs from '@/components/admin/FormTabs.vue';
import ImageUpload from '@/components/admin/ImageUpload.vue';
import RepeaterRows from '@/components/admin/RepeaterRows.vue';
import type { RepeaterRow } from '@/components/admin/RepeaterRows.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Textarea } from '@/components/ui/textarea';
import { edit as settingsEdit } from '@/routes/admin/settings';

type Faq = { question: string; answer: string };
type SettingsGroup =
    'organization' | 'home' | 'facility' | 'contact' | 'pages' | 'seo';
type PageToggle = {
    key: string;
    label: string;
    description: string;
    setting: string;
};

const props = defineProps<{
    group: SettingsGroup;
    groups: { key: SettingsGroup; label: string }[];
    pages: PageToggle[];
    settings: Record<string, unknown>;
    imageUrls: Record<string, string | null>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Site Settings', href: settingsEdit('organization') },
        ],
    },
});

const str = (key: string): string => {
    const value = props.settings[key];

    return value === null || value === undefined ? '' : String(value);
};

const rows = (key: string): RepeaterRow[] => {
    const value = props.settings[key];

    return Array.isArray(value)
        ? (value as Partial<RepeaterRow>[]).map((row) => ({
              title: row.title ?? '',
              description: row.description ?? '',
          }))
        : [];
};

const values = ref(rows('home_values'));
const goals = ref(rows('home_goals'));
const developmentTiers = ref(rows('home_development_tiers'));
const yearRoundItems = ref(rows('home_year_round_items'));
const whatsNewItems = ref(rows('home_whats_new_items'));
const offerings = ref(rows('home_offerings'));
const faqs = ref<Faq[]>(
    Array.isArray(props.settings.seo_faq)
        ? (props.settings.seo_faq as Faq[]).map((f) => ({ ...f }))
        : [],
);

const pageEnabled = ref<Record<string, boolean>>(
    Object.fromEntries(
        props.pages.map((page) => [page.key, !!props.settings[page.setting]]),
    ),
);

const currentLabel =
    props.groups.find((g) => g.key === props.group)?.label ?? 'Settings';
</script>

<template>
    <Head :title="`Site Settings — ${currentLabel}`" />

    <div class="flex flex-col gap-6 p-4 md:p-6">
        <Heading
            title="Site Settings"
            description="Contact details, page copy, and sitewide SEO defaults."
        />

        <nav
            class="flex flex-wrap gap-1 border-b"
            aria-label="Settings sections"
        >
            <Link
                v-for="g in groups"
                :key="g.key"
                :href="settingsEdit(g.key)"
                class="-mb-px border-b-2 px-3 py-2 text-sm font-medium transition-colors"
                :class="
                    g.key === group
                        ? 'border-primary text-foreground'
                        : 'border-transparent text-muted-foreground hover:text-foreground'
                "
            >
                {{ g.label }}
            </Link>
        </nav>

        <Form
            v-bind="SiteSettingController.update.form(group)"
            class="max-w-3xl space-y-8"
            v-slot="{ errors, processing }"
        >
            <!-- Organization -->
            <template v-if="group === 'organization'">
                <p
                    class="rounded-md border bg-muted/40 p-3 text-sm text-muted-foreground"
                >
                    These details appear in the site footer and power the
                    <code>LocalBusiness</code> /
                    <code>SportsOrganization</code> structured data that search
                    engines and AI assistants read. Keep the address and phone
                    identical to your Google Business Profile.
                </p>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid content-start gap-2 sm:col-span-2">
                        <Label for="org_name">Organization name</Label>
                        <Input
                            id="org_name"
                            name="org_name"
                            :default-value="str('org_name')"
                            required
                        />
                        <InputError :message="errors.org_name" />
                    </div>
                    <div class="grid content-start gap-2">
                        <Label for="phone">Phone</Label>
                        <Input
                            id="phone"
                            name="phone"
                            :default-value="str('phone')"
                        />
                        <InputError :message="errors.phone" />
                    </div>
                    <div class="grid content-start gap-2">
                        <Label for="email">Email</Label>
                        <Input
                            id="email"
                            name="email"
                            type="email"
                            :default-value="str('email')"
                        />
                        <InputError :message="errors.email" />
                    </div>
                    <div class="grid content-start gap-2 sm:col-span-2">
                        <Label for="address_line1">Street address</Label>
                        <Input
                            id="address_line1"
                            name="address_line1"
                            :default-value="str('address_line1')"
                            placeholder="Not set yet"
                        />
                        <InputError :message="errors.address_line1" />
                    </div>
                    <div class="grid content-start gap-2">
                        <Label for="address_city">City</Label>
                        <Input
                            id="address_city"
                            name="address_city"
                            :default-value="str('address_city')"
                        />
                        <InputError :message="errors.address_city" />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="grid content-start gap-2">
                            <Label for="address_state">State</Label>
                            <Input
                                id="address_state"
                                name="address_state"
                                :default-value="str('address_state')"
                            />
                            <InputError :message="errors.address_state" />
                        </div>
                        <div class="grid content-start gap-2">
                            <Label for="address_postal_code">ZIP</Label>
                            <Input
                                id="address_postal_code"
                                name="address_postal_code"
                                :default-value="str('address_postal_code')"
                            />
                            <InputError :message="errors.address_postal_code" />
                        </div>
                    </div>
                    <div class="grid content-start gap-2">
                        <Label for="geo_latitude">Latitude</Label>
                        <Input
                            id="geo_latitude"
                            name="geo_latitude"
                            type="number"
                            step="any"
                            :default-value="str('geo_latitude')"
                        />
                        <InputError :message="errors.geo_latitude" />
                    </div>
                    <div class="grid content-start gap-2">
                        <Label for="geo_longitude">Longitude</Label>
                        <Input
                            id="geo_longitude"
                            name="geo_longitude"
                            type="number"
                            step="any"
                            :default-value="str('geo_longitude')"
                        />
                        <InputError :message="errors.geo_longitude" />
                    </div>
                    <div class="grid content-start gap-2">
                        <Label for="service_area">Service area</Label>
                        <Input
                            id="service_area"
                            name="service_area"
                            :default-value="str('service_area')"
                            placeholder="e.g. Naperville, Aurora, and the western suburbs"
                        />
                        <InputError :message="errors.service_area" />
                    </div>
                    <div class="grid content-start gap-2">
                        <Label for="founding_year">Founding year</Label>
                        <Input
                            id="founding_year"
                            name="founding_year"
                            inputmode="numeric"
                            maxlength="4"
                            :default-value="str('founding_year')"
                        />
                        <InputError :message="errors.founding_year" />
                    </div>
                </div>

                <div>
                    <h3 class="mb-3 text-sm font-semibold">Social profiles</h3>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div
                            v-for="key in [
                                'social_facebook',
                                'social_instagram',
                                'social_twitter',
                                'social_youtube',
                                'social_tiktok',
                            ]"
                            :key="key"
                            class="grid content-start gap-2"
                        >
                            <Label :for="key" class="capitalize">{{
                                key.replace('social_', '') === 'twitter'
                                    ? 'X / Twitter'
                                    : key.replace('social_', '')
                            }}</Label>
                            <Input
                                :id="key"
                                :name="key"
                                type="url"
                                :default-value="str(key)"
                                placeholder="https://"
                            />
                            <InputError :message="errors[key]" />
                        </div>
                    </div>
                </div>
            </template>

            <!-- Home -->
            <template v-else-if="group === 'home'">
                <FormTabs
                    :errors="errors"
                    :tabs="[
                        {
                            value: 'hero',
                            prefix: ['home_hero', 'remove_home_hero'],
                        },
                        {
                            value: 'mission',
                            prefix: [
                                'home_mission',
                                'home_intro',
                                'home_belief',
                            ],
                        },
                        { value: 'whats-new', prefix: 'home_whats_new' },
                        {
                            value: 'philosophy',
                            prefix: [
                                'home_philosophy',
                                'home_values',
                                'home_goals',
                            ],
                        },
                        {
                            value: 'development',
                            prefix: ['home_development', 'home_year_round'],
                        },
                        { value: 'about' },
                    ]"
                    class="w-full"
                >
                    <TabsList class="h-auto flex-wrap justify-start">
                        <TabsTrigger value="hero">Hero</TabsTrigger>
                        <TabsTrigger value="mission">Mission</TabsTrigger>
                        <TabsTrigger value="whats-new">What's new</TabsTrigger>
                        <TabsTrigger value="philosophy"
                            >Philosophy & goals</TabsTrigger
                        >
                        <TabsTrigger value="development"
                            >Development</TabsTrigger
                        >
                        <TabsTrigger value="about">About & closing</TabsTrigger>
                    </TabsList>

                    <TabsContent value="hero" class="mt-6 space-y-8">
                        <section class="space-y-4">
                            <h3 class="text-sm font-semibold">Hero</h3>
                            <div class="grid content-start gap-2">
                                <Label for="home_hero_headline">Headline</Label>
                                <Input
                                    id="home_hero_headline"
                                    name="home_hero_headline"
                                    :default-value="str('home_hero_headline')"
                                    required
                                />
                                <InputError
                                    :message="errors.home_hero_headline"
                                />
                            </div>
                            <div class="grid content-start gap-2">
                                <Label for="home_hero_subheadline"
                                    >Sub-headline</Label
                                >
                                <Textarea
                                    id="home_hero_subheadline"
                                    name="home_hero_subheadline"
                                    rows="2"
                                    :default-value="
                                        str('home_hero_subheadline')
                                    "
                                />
                                <InputError
                                    :message="errors.home_hero_subheadline"
                                />
                            </div>
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div class="grid content-start gap-2">
                                    <Label for="home_hero_cta_label"
                                        >Primary button label</Label
                                    >
                                    <Input
                                        id="home_hero_cta_label"
                                        name="home_hero_cta_label"
                                        :default-value="
                                            str('home_hero_cta_label')
                                        "
                                    />
                                    <InputError
                                        :message="errors.home_hero_cta_label"
                                    />
                                </div>
                                <div class="grid content-start gap-2">
                                    <Label for="home_hero_cta_url"
                                        >Primary button link</Label
                                    >
                                    <Input
                                        id="home_hero_cta_url"
                                        name="home_hero_cta_url"
                                        :default-value="
                                            str('home_hero_cta_url')
                                        "
                                        placeholder="/tryouts"
                                    />
                                    <InputError
                                        :message="errors.home_hero_cta_url"
                                    />
                                </div>
                                <div class="grid content-start gap-2">
                                    <Label for="home_hero_secondary_cta_label"
                                        >Secondary button label</Label
                                    >
                                    <Input
                                        id="home_hero_secondary_cta_label"
                                        name="home_hero_secondary_cta_label"
                                        :default-value="
                                            str('home_hero_secondary_cta_label')
                                        "
                                    />
                                    <InputError
                                        :message="
                                            errors.home_hero_secondary_cta_label
                                        "
                                    />
                                </div>
                                <div class="grid content-start gap-2">
                                    <Label for="home_hero_secondary_cta_url"
                                        >Secondary button link</Label
                                    >
                                    <Input
                                        id="home_hero_secondary_cta_url"
                                        name="home_hero_secondary_cta_url"
                                        :default-value="
                                            str('home_hero_secondary_cta_url')
                                        "
                                        placeholder="/contact"
                                    />
                                    <InputError
                                        :message="
                                            errors.home_hero_secondary_cta_url
                                        "
                                    />
                                </div>
                            </div>
                            <ImageUpload
                                name="home_hero_image"
                                label="Hero background image"
                                remove-name="remove_home_hero_image"
                                :current-url="imageUrls.home_hero_image ?? null"
                                :error="errors.home_hero_image"
                                hint="Wide landscape photo works best (also used as the default share image for the home page)."
                            />
                        </section>
                    </TabsContent>

                    <TabsContent value="mission" class="mt-6 space-y-8">
                        <section class="space-y-4">
                            <h3 class="text-sm font-semibold">Our mission</h3>
                            <div class="grid content-start gap-2">
                                <Label for="home_mission_title">Heading</Label>
                                <Input
                                    id="home_mission_title"
                                    name="home_mission_title"
                                    :default-value="str('home_mission_title')"
                                />
                                <InputError
                                    :message="errors.home_mission_title"
                                />
                            </div>
                            <div class="grid content-start gap-2">
                                <Label for="home_intro"
                                    >Opening statement</Label
                                >
                                <Textarea
                                    id="home_intro"
                                    name="home_intro"
                                    rows="3"
                                    :default-value="str('home_intro')"
                                />
                                <p class="text-xs text-muted-foreground">
                                    One direct sentence describing who you are —
                                    shown large at the top of the mission
                                    section and quoted by search engines and AI
                                    assistants.
                                </p>
                                <InputError :message="errors.home_intro" />
                            </div>
                            <div class="grid content-start gap-2">
                                <Label for="home_mission_body">Mission</Label>
                                <Textarea
                                    id="home_mission_body"
                                    name="home_mission_body"
                                    rows="4"
                                    :default-value="str('home_mission_body')"
                                />
                                <InputError
                                    :message="errors.home_mission_body"
                                />
                            </div>
                            <div class="grid content-start gap-2">
                                <Label for="home_belief">What we believe</Label>
                                <Textarea
                                    id="home_belief"
                                    name="home_belief"
                                    rows="3"
                                    :default-value="str('home_belief')"
                                />
                                <p class="text-xs text-muted-foreground">
                                    A single closing statement, shown as a pull
                                    quote.
                                </p>
                                <InputError :message="errors.home_belief" />
                            </div>
                        </section>
                    </TabsContent>

                    <TabsContent value="whats-new" class="mt-6 space-y-8">
                        <section class="space-y-4">
                            <h3 class="text-sm font-semibold">
                                What's new this season
                            </h3>
                            <div class="grid content-start gap-2">
                                <Label for="home_whats_new_title"
                                    >Heading</Label
                                >
                                <Input
                                    id="home_whats_new_title"
                                    name="home_whats_new_title"
                                    :default-value="str('home_whats_new_title')"
                                />
                                <InputError
                                    :message="errors.home_whats_new_title"
                                />
                            </div>
                            <div class="grid content-start gap-2">
                                <Label for="home_whats_new_body">Intro</Label>
                                <Textarea
                                    id="home_whats_new_body"
                                    name="home_whats_new_body"
                                    rows="2"
                                    :default-value="str('home_whats_new_body')"
                                />
                                <InputError
                                    :message="errors.home_whats_new_body"
                                />
                            </div>
                        </section>

                        <RepeaterRows
                            v-model="whatsNewItems"
                            name="home_whats_new_items"
                            label="What's new items"
                            :max="8"
                            :errors="errors"
                            hint="Shown prominently near the top of the home page."
                        />
                    </TabsContent>

                    <TabsContent value="philosophy" class="mt-6 space-y-8">
                        <section class="space-y-4">
                            <h3 class="text-sm font-semibold">
                                Coaching philosophy
                            </h3>
                            <div class="grid content-start gap-2">
                                <Label for="home_philosophy_title"
                                    >Heading</Label
                                >
                                <Input
                                    id="home_philosophy_title"
                                    name="home_philosophy_title"
                                    :default-value="
                                        str('home_philosophy_title')
                                    "
                                />
                                <InputError
                                    :message="errors.home_philosophy_title"
                                />
                            </div>
                            <div class="grid content-start gap-2">
                                <Label for="home_philosophy_body">Intro</Label>
                                <Textarea
                                    id="home_philosophy_body"
                                    name="home_philosophy_body"
                                    rows="4"
                                    :default-value="str('home_philosophy_body')"
                                />
                                <InputError
                                    :message="errors.home_philosophy_body"
                                />
                            </div>
                        </section>

                        <RepeaterRows
                            v-model="values"
                            name="home_values"
                            label="Core values"
                            :max="10"
                            :errors="errors"
                        />

                        <section class="space-y-4 border-t pt-6">
                            <h3 class="text-sm font-semibold">Our goals</h3>
                            <div class="grid content-start gap-2">
                                <Label for="home_goals_title">Heading</Label>
                                <Input
                                    id="home_goals_title"
                                    name="home_goals_title"
                                    :default-value="str('home_goals_title')"
                                />
                                <InputError
                                    :message="errors.home_goals_title"
                                />
                            </div>
                            <div class="grid content-start gap-2">
                                <Label for="home_goals_body">Intro</Label>
                                <Textarea
                                    id="home_goals_body"
                                    name="home_goals_body"
                                    rows="2"
                                    :default-value="str('home_goals_body')"
                                />
                                <InputError :message="errors.home_goals_body" />
                            </div>
                        </section>

                        <RepeaterRows
                            v-model="goals"
                            name="home_goals"
                            label="Team goals"
                            :max="6"
                            :errors="errors"
                        />
                    </TabsContent>

                    <TabsContent value="development" class="mt-6 space-y-8">
                        <section class="space-y-4">
                            <h3 class="text-sm font-semibold">
                                Player development
                            </h3>
                            <div class="grid content-start gap-2">
                                <Label for="home_development_title"
                                    >Heading</Label
                                >
                                <Input
                                    id="home_development_title"
                                    name="home_development_title"
                                    :default-value="
                                        str('home_development_title')
                                    "
                                />
                                <InputError
                                    :message="errors.home_development_title"
                                />
                            </div>
                            <div class="grid content-start gap-2">
                                <Label for="home_development_body">Intro</Label>
                                <Textarea
                                    id="home_development_body"
                                    name="home_development_body"
                                    rows="4"
                                    :default-value="
                                        str('home_development_body')
                                    "
                                />
                                <p class="text-xs text-muted-foreground">
                                    Explain the professional coaching model and
                                    the commitment to every age level.
                                </p>
                                <InputError
                                    :message="errors.home_development_body"
                                />
                            </div>
                        </section>

                        <RepeaterRows
                            v-model="developmentTiers"
                            name="home_development_tiers"
                            label="Age groups"
                            :max="4"
                            :errors="errors"
                            hint="Title is the age group (e.g. 10U – 11U); description is the focus at that level."
                        />

                        <section class="space-y-4 border-t pt-6">
                            <h3 class="text-sm font-semibold">
                                Year-round development
                            </h3>
                            <div class="grid content-start gap-2">
                                <Label for="home_year_round_title"
                                    >Heading</Label
                                >
                                <Input
                                    id="home_year_round_title"
                                    name="home_year_round_title"
                                    :default-value="
                                        str('home_year_round_title')
                                    "
                                />
                                <InputError
                                    :message="errors.home_year_round_title"
                                />
                            </div>
                            <div class="grid content-start gap-2">
                                <Label for="home_year_round_body">Intro</Label>
                                <Textarea
                                    id="home_year_round_body"
                                    name="home_year_round_body"
                                    rows="3"
                                    :default-value="str('home_year_round_body')"
                                />
                                <InputError
                                    :message="errors.home_year_round_body"
                                />
                            </div>
                        </section>

                        <RepeaterRows
                            v-model="yearRoundItems"
                            name="home_year_round_items"
                            label="Winter training includes"
                            :max="8"
                            :errors="errors"
                        />
                    </TabsContent>

                    <TabsContent value="about" class="mt-6 space-y-8">
                        <RepeaterRows
                            v-model="offerings"
                            name="home_offerings"
                            label="What we offer"
                            :max="6"
                            :errors="errors"
                            hint="Short cards linking the basics: teams, coaching, camps, facility."
                        />

                        <section class="space-y-4 border-t pt-6">
                            <h3 class="text-sm font-semibold">About</h3>
                            <div class="grid content-start gap-2">
                                <Label for="home_about_heading">Heading</Label>
                                <Input
                                    id="home_about_heading"
                                    name="home_about_heading"
                                    :default-value="str('home_about_heading')"
                                />
                                <InputError
                                    :message="errors.home_about_heading"
                                />
                            </div>
                            <div class="grid content-start gap-2">
                                <Label for="home_about_body">Body</Label>
                                <Textarea
                                    id="home_about_body"
                                    name="home_about_body"
                                    rows="6"
                                    :default-value="str('home_about_body')"
                                />
                                <InputError :message="errors.home_about_body" />
                            </div>
                            <ImageUpload
                                name="home_about_image"
                                label="About image"
                                remove-name="remove_home_about_image"
                                :current-url="
                                    imageUrls.home_about_image ?? null
                                "
                                :error="errors.home_about_image"
                            />
                            <div class="grid content-start gap-2">
                                <Label for="home_youtube_url"
                                    >YouTube video URL</Label
                                >
                                <Input
                                    id="home_youtube_url"
                                    name="home_youtube_url"
                                    type="url"
                                    :default-value="str('home_youtube_url')"
                                    placeholder="https://www.youtube.com/watch?v=…"
                                />
                                <InputError
                                    :message="errors.home_youtube_url"
                                />
                            </div>
                        </section>

                        <section class="space-y-4 border-t pt-6">
                            <h3 class="text-sm font-semibold">
                                Closing call to action
                            </h3>
                            <div class="grid content-start gap-2">
                                <Label for="home_closing_title">Heading</Label>
                                <Input
                                    id="home_closing_title"
                                    name="home_closing_title"
                                    :default-value="str('home_closing_title')"
                                />
                                <InputError
                                    :message="errors.home_closing_title"
                                />
                            </div>
                            <div class="grid content-start gap-2">
                                <Label for="home_closing_body">Body</Label>
                                <Textarea
                                    id="home_closing_body"
                                    name="home_closing_body"
                                    rows="3"
                                    :default-value="str('home_closing_body')"
                                />
                                <InputError
                                    :message="errors.home_closing_body"
                                />
                            </div>
                        </section>
                    </TabsContent>
                </FormTabs>
            </template>

            <!-- Facility -->
            <template v-else-if="group === 'facility'">
                <div class="grid content-start gap-2">
                    <Label for="facility_heading">Heading</Label>
                    <Input
                        id="facility_heading"
                        name="facility_heading"
                        :default-value="str('facility_heading')"
                        required
                    />
                    <InputError :message="errors.facility_heading" />
                </div>
                <div class="grid content-start gap-2">
                    <Label for="facility_description">Description</Label>
                    <Textarea
                        id="facility_description"
                        name="facility_description"
                        rows="6"
                        :default-value="str('facility_description')"
                    />
                    <InputError :message="errors.facility_description" />
                </div>
                <div class="grid content-start gap-2">
                    <Label for="facility_address">Facility address</Label>
                    <Input
                        id="facility_address"
                        name="facility_address"
                        :default-value="str('facility_address')"
                    />
                    <InputError :message="errors.facility_address" />
                </div>
                <div class="grid content-start gap-2">
                    <Label for="facility_youtube_url">YouTube tour URL</Label>
                    <Input
                        id="facility_youtube_url"
                        name="facility_youtube_url"
                        type="url"
                        :default-value="str('facility_youtube_url')"
                    />
                    <InputError :message="errors.facility_youtube_url" />
                </div>
                <p class="text-sm text-muted-foreground">
                    Facility photos are managed under
                    <strong>Facility Photos</strong> in the sidebar.
                </p>
            </template>

            <!-- Contact -->
            <template v-else-if="group === 'contact'">
                <div class="grid content-start gap-2">
                    <Label for="contact_intro">Intro text</Label>
                    <Textarea
                        id="contact_intro"
                        name="contact_intro"
                        rows="3"
                        :default-value="str('contact_intro')"
                    />
                    <InputError :message="errors.contact_intro" />
                </div>
                <p class="text-sm text-muted-foreground">
                    Phone, email, and address shown on the contact page come
                    from the
                    <Link :href="settingsEdit('organization')" class="underline"
                        >Organization</Link
                    >
                    tab.
                </p>
            </template>

            <!-- Pages -->
            <template v-else-if="group === 'pages'">
                <p
                    class="rounded-md border bg-muted/40 p-3 text-sm text-muted-foreground"
                >
                    Switch a page off while it is still being built. It
                    disappears from the header, footer, sitemap, and
                    <code>llms.txt</code>, and anyone who opens its URL gets a
                    404. Turning off Merch also hides the cart icon and closes
                    checkout.
                </p>
                <div class="divide-y rounded-md border">
                    <label
                        v-for="page in pages"
                        :key="page.key"
                        class="flex items-start gap-3 p-4 text-sm"
                    >
                        <input
                            type="hidden"
                            :name="page.setting"
                            :value="pageEnabled[page.key] ? 1 : 0"
                        />
                        <Switch
                            v-model="pageEnabled[page.key]"
                            class="mt-0.5"
                        />
                        <span class="grid gap-1">
                            <span class="font-medium">{{ page.label }}</span>
                            <span class="text-muted-foreground">{{
                                page.description
                            }}</span>
                        </span>
                    </label>
                </div>
                <p class="text-sm text-muted-foreground">
                    Tryouts are not listed: those links show up on their own
                    whenever a tryout is open for registration, and hide
                    themselves the rest of the time.
                </p>
            </template>

            <!-- SEO -->
            <template v-else-if="group === 'seo'">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid content-start gap-2">
                        <Label for="seo_site_name">Site name</Label>
                        <Input
                            id="seo_site_name"
                            name="seo_site_name"
                            :default-value="str('seo_site_name')"
                            required
                        />
                        <InputError :message="errors.seo_site_name" />
                    </div>
                    <div class="grid content-start gap-2">
                        <Label for="seo_title_template">Title template</Label>
                        <Input
                            id="seo_title_template"
                            name="seo_title_template"
                            :default-value="str('seo_title_template')"
                            required
                        />
                        <p class="text-xs text-muted-foreground">
                            <code>%s</code> is replaced with the page title.
                        </p>
                        <InputError :message="errors.seo_title_template" />
                    </div>
                </div>
                <div class="grid content-start gap-2">
                    <Label for="seo_default_description"
                        >Default meta description</Label
                    >
                    <Textarea
                        id="seo_default_description"
                        name="seo_default_description"
                        rows="3"
                        maxlength="320"
                        :default-value="str('seo_default_description')"
                    />
                    <InputError :message="errors.seo_default_description" />
                </div>
                <ImageUpload
                    name="seo_default_share_image"
                    label="Default share image"
                    aspect="share"
                    remove-name="remove_seo_default_share_image"
                    :current-url="imageUrls.seo_default_share_image ?? null"
                    :error="errors.seo_default_share_image"
                    hint="Used when a page has no image of its own. Cropped to 1200×630 (min 600×315). Ideally your logo on a navy background."
                />
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid content-start gap-2">
                        <Label for="seo_google_site_verification"
                            >Google Search Console verification</Label
                        >
                        <Input
                            id="seo_google_site_verification"
                            name="seo_google_site_verification"
                            :default-value="str('seo_google_site_verification')"
                        />
                        <InputError
                            :message="errors.seo_google_site_verification"
                        />
                    </div>
                    <div class="grid content-start gap-2">
                        <Label for="seo_bing_site_verification"
                            >Bing Webmaster verification</Label
                        >
                        <Input
                            id="seo_bing_site_verification"
                            name="seo_bing_site_verification"
                            :default-value="str('seo_bing_site_verification')"
                        />
                        <InputError
                            :message="errors.seo_bing_site_verification"
                        />
                    </div>
                    <div class="grid content-start gap-2">
                        <Label for="seo_facebook_app_id">Facebook App ID</Label>
                        <Input
                            id="seo_facebook_app_id"
                            name="seo_facebook_app_id"
                            :default-value="str('seo_facebook_app_id')"
                        />
                        <InputError :message="errors.seo_facebook_app_id" />
                    </div>
                    <div class="grid content-start gap-2">
                        <Label for="seo_twitter_handle"
                            >X / Twitter handle</Label
                        >
                        <Input
                            id="seo_twitter_handle"
                            name="seo_twitter_handle"
                            :default-value="str('seo_twitter_handle')"
                            placeholder="@eaglesbaseball"
                        />
                        <InputError :message="errors.seo_twitter_handle" />
                    </div>
                </div>

                <section class="space-y-4 border-t pt-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-semibold">Home page FAQ</h3>
                            <p class="text-xs text-muted-foreground">
                                Shown on the home page and published as FAQ
                                structured data — great for AI answers.
                            </p>
                        </div>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            :disabled="faqs.length >= 20"
                            @click="faqs.push({ question: '', answer: '' })"
                        >
                            <Plus class="size-4" /> Add question
                        </Button>
                    </div>
                    <InputError :message="errors.seo_faq" />
                    <div
                        v-for="(faq, i) in faqs"
                        :key="i"
                        class="grid gap-3 rounded-md border p-3"
                    >
                        <div class="flex items-start gap-2">
                            <div class="grid flex-1 gap-2">
                                <Label :for="`faq_q_${i}`">Question</Label>
                                <Input
                                    :id="`faq_q_${i}`"
                                    v-model="faq.question"
                                    :name="`seo_faq[${i}][question]`"
                                    required
                                />
                                <InputError
                                    :message="errors[`seo_faq.${i}.question`]"
                                />
                            </div>
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon"
                                class="mt-6 text-destructive"
                                aria-label="Remove question"
                                @click="faqs.splice(i, 1)"
                            >
                                <Trash2 class="size-4" />
                            </Button>
                        </div>
                        <div class="grid content-start gap-2">
                            <Label :for="`faq_a_${i}`">Answer</Label>
                            <Textarea
                                :id="`faq_a_${i}`"
                                v-model="faq.answer"
                                :name="`seo_faq[${i}][answer]`"
                                rows="3"
                                required
                            />
                            <InputError
                                :message="errors[`seo_faq.${i}.answer`]"
                            />
                        </div>
                    </div>
                    <p
                        v-if="faqs.length === 0"
                        class="text-sm text-muted-foreground"
                    >
                        No questions yet.
                    </p>
                </section>
            </template>

            <div class="flex items-center gap-4 border-t pt-6">
                <Button type="submit" :disabled="processing"
                    >Save settings</Button
                >
            </div>
        </Form>
    </div>
</template>
