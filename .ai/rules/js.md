---
paths:
  - 'resources/js/**'
---

# Js

## Page layout mapping, brand tokens, and shared components
app.ts maps `site/*` pages → PublicLayout (header/footer/SeoHead), `admin/*` → AppLayout sidebar, `auth/*` + `invitations/*` → AuthLayout. Brand tokens in app.css: navy #161A35, sky #6D96B6, stone #B7B6B4, snow #FEFEFE (`bg-navy text-snow`, `bg-sky text-navy`); primary = sky; headings use `font-display` (Barlow Condensed) uppercase; page width utility `container-site`. Reuse @/components/admin/{ImageUpload,SeoFields,ConfirmDelete,Pagination,EmptyState,StatusBadge} and @/components/site/{PageHero,SectionHeading,ShareButtons,YouTubeEmbed,RegistrationStateBadge}; money/date helpers in @/lib/format (America/Chicago). Lucide v1 has no brand icons — use @/components/site/icons/*. Wayfinder must be generated with `--with-form` (vite plugin uses formVariants).

## Admin forms that span tabs must use FormTabs
reka-ui unmounts inactive tab panels by default, so a form whose fields span tabs submits only the visible tab's inputs — the request then fails validation on fields hidden behind another tab and "Save changes" appears to do nothing. Two guards, both already in place:

1. `components/ui/tabs/Tabs.vue` overrides `unmountOnHide` to false, so hidden panels stay mounted (Tailwind preflight's `[hidden] { display: none !important }` keeps them invisible over any `grid`/`flex` utility on the panel). It also sets `novalidate` on the enclosing `<form>`, because a `required` input on a hidden panel is unfocusable and browsers abort the submit silently.
2. `components/admin/FormTabs.vue` wraps Tabs, takes `:errors` plus a `:tabs` map, and opens the tab owning the first error so the message is never hidden. Use it — not raw `<Tabs>` — for any admin form whose fields span tabs, and add the new tab to its `:tabs` list. Raw `<Tabs>` is fine only when each tab holds its own separate `<Form>` (see pages/admin/navigation/Index.vue).

tests/Feature/Admin/TabbedFormsTest.php pins both.
