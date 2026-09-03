---
paths:
  - 'app/**'
---

# App

## Roles, money, timezone, and settings conventions
Roles: `users.role` enum (App\Enums\UserRole admin|staff); public registration is disabled — staff join via Invitation accept links; the `admin` middleware alias / Gate `admin` guards Site Settings, SEO defaults, Users & Invites. All money is integer cents (products.price, orders.total, camps.price); admin forms accept dollars and convert. App timezone is America/Chicago. Editable copy lives in `site_settings` via App\Services\SiteSettings (defaults + groups in SiteSettings::DEFAULTS/GROUPS, cached forever, flushed on write). Org inbox for notifications = SiteSettings 'email' (default eaglesbaseballtravel@gmail.com).

## Tryout links hide themselves — never add a manual toggle
Public tryout links/buttons appear only while at least one tryout is open for registration. The single source of truth is `Tryout::openForRegistration()` (the query form of `HasRegistrationWindow::isRegistrationOpen()`), read through the scoped `App\Services\TryoutAvailability` service.

It is applied centrally: `Navigation::forFrontend()` strips tryout items from every menu and forces `nav_show_cta` off; `HomeController` blanks a hero CTA pointing at tryouts and passes an empty tryouts list; `HandleInertiaRequests` shares `tryoutsOpen` for pages with hardcoded links (site/Teams/Index.vue). The `/tryouts` page itself stays reachable and shows its own empty state.

Menu filtering happens AFTER the forever-cached `navigation.menus` — availability moves with the clock and must never be baked into that cache, nor memoized beyond one request (hence `scoped`, not `singleton`). New tryout links: gate on the `tryoutsOpen` shared prop, or route the href through `TryoutAvailability::hidesLink()` server-side. Do not add a site setting to toggle them.

## Ordering is drag-and-drop from the index, never a sort_order form field
Owner decision (2026-08-26): admin lists are ordered by dragging rows on the index page. Never add a `sort_order` input to a form or accept it in a Store*/Update* request.

Server: models with a `sort_order` column use `App\Models\Concerns\HasSortOrder` (Team, Coach, Product). `nextSortOrder()` appends a newly created record to the end — set it in `store()`, not in the shared attributes helper used by `update()`. `applyManualOrder($ids)` normalises every row to a unique slot, then permutes only the slots the submitted ids already occupy, so dragging within one paginated page never disturbs rows on other pages. Each resource gets `POST {resource}/reorder` → `Reorder*Request` (validates `order.*` exists) → `back()`.

Client: `resources/js/composables/useSortableList.ts` (SortableJS, imported dynamically so SSR stays safe) plus `components/admin/DragHandle.vue`. Bind `ref="container"` to `<TableBody>`, render the composable's `items` instead of the paginator prop, and wire `@move` for the arrow-key fallback — drag alone is not keyboard accessible. Navigation and facility photos predate this and still use their own arrow buttons.

## Unfinished pages are switched off in Site Settings → Pages
Public pages that are not ready for launch are toggled from Site Settings → Pages, backed by `page_{key}_enabled` booleans (SiteSettings::GROUPS['pages']). `App\Services\PageVisibility` (singleton) is the single source of truth: PAGES lists the toggleable pages (teams, facility, coaches, camps, merch, contact) with the route names that belong to each.

A switched-off page 404s via the `page:{key}` middleware alias (EnsurePageIsEnabled) applied in routes/web.php, disappears from every menu and the header CTA (Navigation::forFrontend), and drops out of the sitemap and llms.txt. Merch owns the cart and checkout: switching it off closes /cart and /checkout and forces `nav_show_cart` off, which hides the cart icon. Saving the group also flushes SitemapController::CACHE_KEY.

New public pages: add an entry to PageVisibility::PAGES and put the routes behind `page:{key}` — do not add ad-hoc `if` checks in controllers or Vue. Tryouts are deliberately not toggleable; their links already hide themselves via TryoutAvailability.
