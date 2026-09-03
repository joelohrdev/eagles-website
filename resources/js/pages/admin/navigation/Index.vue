<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import NavigationController from '@/actions/App/Http/Controllers/Admin/NavigationController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Textarea } from '@/components/ui/textarea';
import MenuEditor from '@/pages/admin/navigation/MenuEditor.vue';
import type { MenuItem, PageOption } from '@/pages/admin/navigation/types';
import { index } from '@/routes/admin/navigation';
import type { NavigationSettings } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Navigation & Footer', href: index() }],
    },
});

const props = defineProps<{
    menus: {
        header: MenuItem[];
        footer: MenuItem[];
        footer_bottom: MenuItem[];
    };
    pages: PageOption[];
    settings: NavigationSettings;
}>();

const showCta = ref(props.settings.nav_show_cta);
const showCart = ref(props.settings.nav_show_cart);
const showContact = ref(props.settings.footer_show_contact);
const showSocials = ref(props.settings.footer_show_socials);
const showAddress = ref(props.settings.footer_show_address);
</script>

<template>
    <Head title="Navigation & Footer" />

    <div class="flex flex-col gap-6 p-4 md:p-6">
        <Heading
            title="Navigation & Footer"
            description="Control the links in the site header and footer, their order, and the footer content."
        />

        <Tabs default-value="header" class="w-full">
            <TabsList>
                <TabsTrigger value="header">Header</TabsTrigger>
                <TabsTrigger value="footer">Footer</TabsTrigger>
            </TabsList>

            <TabsContent value="header" class="mt-4 grid gap-6">
                <MenuEditor
                    location="header"
                    title="Main menu"
                    description="Drag the handle to reorder. Hidden links stay saved but don't show on the site."
                    :items="menus.header"
                    :pages="pages"
                    :max-items="8"
                />

                <Form
                    v-bind="NavigationController.updateSettings.form()"
                    :options="{ preserveScroll: true }"
                    class="rounded-lg border bg-card"
                    v-slot="{ errors, processing }"
                >
                    <header class="border-b p-4">
                        <h2 class="font-semibold">Header options</h2>
                        <p class="text-sm text-muted-foreground">
                            The highlighted call-to-action button and the cart
                            icon.
                        </p>
                    </header>
                    <div class="grid gap-5 p-4 sm:grid-cols-2">
                        <div class="grid content-start gap-2">
                            <Label for="nav_cta_label">Button label</Label>
                            <Input
                                id="nav_cta_label"
                                name="nav_cta_label"
                                :default-value="settings.nav_cta_label ?? ''"
                                maxlength="30"
                            />
                            <InputError :message="errors.nav_cta_label" />
                        </div>
                        <div class="grid content-start gap-2">
                            <Label for="nav_cta_url">Button link</Label>
                            <Input
                                id="nav_cta_url"
                                name="nav_cta_url"
                                :default-value="settings.nav_cta_url ?? ''"
                                placeholder="/tryouts"
                            />
                            <InputError :message="errors.nav_cta_url" />
                        </div>
                        <label class="flex items-center gap-3 text-sm">
                            <input
                                type="hidden"
                                name="nav_show_cta"
                                :value="showCta ? 1 : 0"
                            />
                            <Switch v-model="showCta" />
                            Show the call-to-action button
                        </label>
                        <label class="flex items-center gap-3 text-sm">
                            <input
                                type="hidden"
                                name="nav_show_cart"
                                :value="showCart ? 1 : 0"
                            />
                            <Switch v-model="showCart" />
                            Show the cart icon
                        </label>
                    </div>
                    <footer class="border-t p-4">
                        <Button :disabled="processing"
                            >Save header options</Button
                        >
                    </footer>
                </Form>
            </TabsContent>

            <TabsContent value="footer" class="mt-4 grid gap-6">
                <MenuEditor
                    location="footer"
                    title="Footer links"
                    description="The link column in the footer. Drag to reorder."
                    :items="menus.footer"
                    :pages="pages"
                />
                <MenuEditor
                    location="footer_bottom"
                    title="Bottom bar links"
                    description="Small links next to the copyright line (e.g. Contact, Privacy)."
                    :items="menus.footer_bottom"
                    :pages="pages"
                    :max-items="5"
                />

                <Form
                    v-bind="NavigationController.updateSettings.form()"
                    :options="{ preserveScroll: true }"
                    class="rounded-lg border bg-card"
                    v-slot="{ errors, processing }"
                >
                    <header class="border-b p-4">
                        <h2 class="font-semibold">Footer content</h2>
                        <p class="text-sm text-muted-foreground">
                            Phone, email, address, and social links come from
                            Site Settings → Organization; toggle their
                            visibility here.
                        </p>
                    </header>
                    <div class="grid gap-5 p-4 sm:grid-cols-2">
                        <div class="grid content-start gap-2 sm:col-span-2">
                            <Label for="footer_tagline">Tagline</Label>
                            <Textarea
                                id="footer_tagline"
                                name="footer_tagline"
                                rows="2"
                                maxlength="255"
                                :default-value="settings.footer_tagline ?? ''"
                            />
                            <InputError :message="errors.footer_tagline" />
                        </div>
                        <div class="grid content-start gap-2">
                            <Label for="footer_links_heading"
                                >Links column heading</Label
                            >
                            <Input
                                id="footer_links_heading"
                                name="footer_links_heading"
                                :default-value="
                                    settings.footer_links_heading ?? ''
                                "
                                maxlength="40"
                            />
                            <InputError
                                :message="errors.footer_links_heading"
                            />
                        </div>
                        <div class="grid content-start gap-2">
                            <Label for="footer_contact_heading"
                                >Contact column heading</Label
                            >
                            <Input
                                id="footer_contact_heading"
                                name="footer_contact_heading"
                                :default-value="
                                    settings.footer_contact_heading ?? ''
                                "
                                maxlength="40"
                            />
                            <InputError
                                :message="errors.footer_contact_heading"
                            />
                        </div>
                        <div class="grid content-start gap-2 sm:col-span-2">
                            <Label for="footer_copyright">Copyright line</Label>
                            <Input
                                id="footer_copyright"
                                name="footer_copyright"
                                :default-value="settings.footer_copyright ?? ''"
                                maxlength="255"
                            />
                            <p class="text-xs text-muted-foreground">
                                Use <code>{year}</code> for the current year and
                                <code>{org}</code> for the organization name.
                            </p>
                            <InputError :message="errors.footer_copyright" />
                        </div>
                        <label class="flex items-center gap-3 text-sm">
                            <input
                                type="hidden"
                                name="footer_show_contact"
                                :value="showContact ? 1 : 0"
                            />
                            <Switch v-model="showContact" />
                            Show the contact column
                        </label>
                        <label class="flex items-center gap-3 text-sm">
                            <input
                                type="hidden"
                                name="footer_show_address"
                                :value="showAddress ? 1 : 0"
                            />
                            <Switch v-model="showAddress" />
                            Show the address (when set)
                        </label>
                        <label class="flex items-center gap-3 text-sm">
                            <input
                                type="hidden"
                                name="footer_show_socials"
                                :value="showSocials ? 1 : 0"
                            />
                            <Switch v-model="showSocials" />
                            Show social icons (when set)
                        </label>
                    </div>
                    <footer class="border-t p-4">
                        <Button :disabled="processing"
                            >Save footer content</Button
                        >
                    </footer>
                </Form>
            </TabsContent>
        </Tabs>
    </div>
</template>
