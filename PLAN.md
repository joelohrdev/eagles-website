# Eagles Baseball Travel — Build Plan

**Contact:** 630-767-9208 · eaglesbaseballtravel@gmail.com
**Stack (already installed):** Laravel 13 · PHP 8.5 · Inertia v3 · Vue 3 + TypeScript · Tailwind v4 · reka-ui (shadcn-vue) · Fortify auth · Wayfinder · Pest 5

---

## 1. Recommendation: Purpose-built managers, not a page builder

**Build dedicated backend managers for each content type. Do not build a WordPress-style page builder.**

Why:

| | Custom page builder | Purpose-built managers (recommended) |
|---|---|---|
| Scope | Block schema, drag/drop editor, block renderer, versioning, previews — weeks of work before a single page ships | Each page is a CRUD form + a Vue page; ships in hours/days |
| Data | Blobs of JSON — hard to query ("tryouts open now", "orders awaiting fulfillment") | Real tables — teams, camps, coaches, tryouts, products, orders are all *structured* data with rules attached (dates, prices, sizes) |
| Design quality | Editor decides layout → inconsistent look | Layout is fixed in Vue components → consistent, on-brand, responsive |
| Type safety | Loose | Wayfinder + typed Inertia props end to end |
| Who edits | Non-technical org staff who mostly need to *update a roster, add a camp, open tryouts* — not compose layouts | Exactly matches how they'll actually use it |

The site has 8 known pages and 6 of them are lists of structured records. Only Home, Facility, and Contact are "content pages," and their structure is fixed too (a hero, an offerings list, an about blurb, photos, contact info).

**Hybrid escape hatch (cheap, covers the "what if we need a new page" case):**
- A `site_settings` key/value store for editable copy on fixed pages (hero headline, CTA text, about paragraph, facility description, phone, email, socials).
- A generic `pages` table (slug, title, rich-text body, published flag) for one-off informational pages (e.g. "Policies", "FAQ") rendered by a single generic template. This gives 80% of the page-builder value at 5% of the cost, and can be added in Phase 4 only if it's actually needed.

---

## 2. Architecture

```
Public site (guest)                    Admin (auth, /admin)
─────────────────────                  ─────────────────────
PublicLayout.vue                       AppLayout.vue (existing sidebar shell)
  Home, Teams, Facility, Camps,          Dashboard, Teams, Coaches, Camps,
  Coaches, Tryouts (+register),          Facility Photos, Tryouts (+registrations),
  Merch (+cart, checkout), Contact       Products, Orders, Contact Submissions,
                                         Site Settings
```

- **Auth & roles:** Fortify is already wired. Public registration disabled; access is **invite-only**. Two roles via a `role` enum on `users` (`admin`, `staff`):
  - **Admin** (you): everything below plus Users & Invitations (invite/revoke/change role), Site Settings, SEO defaults, Stripe/Resend config visibility.
  - **Staff**: manage content (teams, coaches, camps, facility, tryouts, registrations, products, orders, contact inbox, per-record SEO & Sharing).
  - Flow: admin enters email + role → `invitations` row + signed, expiring accept link emailed via Resend → invitee sets name/password → account created with that role. Enforced with a `role` middleware/gate + Policies on the admin-only resources; Pest tests cover staff being denied admin-only routes.
- **Controllers:** `App\Http\Controllers\Site\*` (public) and `App\Http\Controllers\Admin\*` (resource controllers). Form Requests for all validation. Policies not needed initially (all authenticated users are admins).
- **Frontend:** `resources/js/pages/site/*` and `resources/js/pages/admin/*`. Reuse existing `components/ui/*`. New `PublicLayout.vue` with header nav + footer (contact info, socials).
- **Routing:** named routes; frontend uses Wayfinder (`@/actions`, `@/routes`) — no hard-coded URLs.
- **Files/images:** Laravel 13's first-party `Image` facade (`Illuminate\Support\Facades\Image`, GD driver by default — no extra package). A small `ImageUpload` service wraps `Image::fromUpload($file)->orient()->scale(width: 1600)->optimize('webp')->storePublicly('teams', 'public')` and generates a thumbnail variant (`cover(600, 400)`) for cards/galleries. Stored on the local `public` disk (`storage:link`) — volume is modest; S3/R2 is a config swap later if it grows. **Video is not uploaded**: Facility/Home/Camp records take an optional YouTube URL and render a responsive, lazy-loaded embed. Validation via `File::image()->max(...)` + `Rule::dimensions()`.
- **Email:** **Resend** via Laravel's built-in `resend` mailer (`composer require resend/resend-php`, `RESEND_API_KEY`, sending domain verified in Resend). Org inbox gets contact submissions, tryout registrations, camp registrations, and paid-order notices; registrant/customer gets confirmations/receipts. Queue via existing `jobs` table (`QUEUE_CONNECTION=database`). Local dev uses `log` mailer.
- **Payments:** Stripe Checkout (hosted page) via `stripe/stripe-php` — used for **merch orders and camp registrations** (tryouts are free, no Stripe involved). One `PaymentService`/`CheckoutSession` abstraction so both flows share the create-session → redirect → webhook → mark-paid path. Dev/staging uses your Stripe **test** keys; production keys (`STRIPE_KEY`, `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET`) are supplied by the org at launch and swapped in `.env` — no code change. Webhook endpoint registered in each Stripe account.
- **SEO/GEO:** Inertia SSR enabled (`@inertiajs/vite` — automatic in dev, `npm run build:ssr` for prod) so meta tags, JSON-LD, and content are in the initial HTML for search and AI crawlers. Per-page meta is managed in the admin (see §5b) and rendered through a shared `useSeo()` composable + `<Head>`.
- **Testing:** Pest feature tests per resource (index/create/update/delete, validation, guest cannot access admin), tryout registration window logic, cart/checkout order creation, webhook handling (with a fake Stripe event), contact form mail.

---

## 3. Data Model

| Table | Key columns | Notes |
|---|---|---|
| `users` (existing) | + `role` enum (`admin`/`staff`) | Seeder creates your admin account |
| `invitations` | email, role, token (hashed), invited_by, expires_at, accepted_at | Invite-only onboarding |
| `site_settings` | `key` (unique), `value` (text/json) | Home hero/CTA copy, "what we offer" items, about text, facility description, contact info, socials |
| `teams` | name, age_group/division, season, description, photo_path, coach_id (nullable FK), sort_order, is_active | Cards only on the public Teams page — no detail pages |
| `coaches` | name, title/role, bio, photo_path, email (nullable), phone (nullable), sort_order, is_active | Coaching Staff page; linkable from teams |
| `camps` | name, slug, description, location, starts_at, ends_at, price (cents), capacity (nullable), age_range, registration_opens_at, registration_closes_at, image_path, youtube_url (nullable), is_published | Camps page + on-site registration + Stripe payment |
| `camp_registrations` | camp_id, order_id (nullable FK → `orders`), player_first/last_name, birthdate, parent_name, email, phone, emergency_contact, medical_notes (nullable), status (pending_payment/paid/cancelled/refunded), registered_at | Created on form submit → Stripe Checkout → webhook marks paid. Admin list/export per camp; unpaid registrations auto-expire and free the spot |
| `facility_photos` | image_path, caption, sort_order | Facility gallery |
| `tryouts` | title, slug, division, location, event_at (date/time), registration_opens_at, registration_closes_at, capacity (nullable), description, is_published | Free — no payment step. `isOpenForRegistration()` accessor drives the public form |
| `tryout_registrations` | tryout_id, player_first/last_name, birthdate, parent_name, email, phone, current_team (nullable), position (nullable), notes, registered_at | Admin can view/export CSV; confirmation email sent |
| `products` | name, slug, description, price (integer cents), image_path, is_active, sort_order | |
| `product_variants` (or JSON) | product_id, size, color, sku (nullable), stock (nullable), price_override (nullable) | Recommend a real table so orders reference an exact size/color |
| `orders` | number, type (`merch`/`camp`), email, name, phone, shipping/pickup fields (merch only), subtotal, total, status (pending/paid/fulfilled/cancelled/refunded), stripe_checkout_session_id, stripe_payment_intent_id, paid_at | Guest checkout (no customer accounts). Camp registrations create a `camp` order so all Stripe payments live in one place |
| `order_items` | order_id, product_variant_id, product_name (snapshot), size, color, unit_price, quantity | Snapshot names/prices at time of order |
| `contact_submissions` | name, email, phone, subject, message, read_at | Stored + emailed to org |
| `seo_meta` | metable_type/metable_id (nullable morph) **or** `route_key` (e.g. `home`, `teams.index`), title, description, canonical_url, robots (index/noindex), **share_title, share_description, share_image_path** (1200×630, auto-cropped on upload), share_image_alt, twitter_card (`summary_large_image` default), json_ld (json, nullable override) | One row per static page (keyed by route name) and per-record overrides for teams/camps/tryouts/products. Search copy and social-share copy are separate fields because they serve different readers; every field falls back to sensible auto-generated values when blank |
| `seo_defaults` (in `site_settings`) | site_name, title_template (`%s | Eagles Baseball Travel`), default_description, default_og_image, organization info (name, address, geo lat/lng, phone, email, socials, founding year, service area) | Feeds the sitewide `SportsOrganization` / `LocalBusiness` JSON-LD |

Money stored as integer cents. All public listings filter on `is_active`/`is_published` and order by `sort_order`.

---

## 4. Pages & Routes

### Public
| Route | Page | Content |
|---|---|---|
| `GET /` | Home | Hero + CTA (→ Tryouts/Contact), "What we offer" cards, About section, contact strip |
| `GET /teams` | Teams | Team cards grouped by division/age; head coach (cards only, no detail pages) |
| `GET /facility` | Facility | Description + photo gallery (lightbox) |
| `GET /camps` | Camps | Upcoming camps (date, location, price, "Register" when window open); past camps hidden |
| `GET /camps/{camp}/register`, `POST …` | Camp registration | Custom form → creates registration + pending order → redirects to Stripe Checkout; success page confirms; webhook marks paid + emails receipt |
| `GET /coaches` | Coaching Staff | Coach cards with bio |
| `GET /tryouts` | Tryouts | List of tryouts; each shows date/time, location, division, and "Register" only when within the open window; closed/upcoming state messaging |
| `GET /tryouts/{tryout}/register`, `POST …` | Tryout registration | Custom form; server enforces window + capacity; confirmation email |
| `GET /merch` | Merch | Product grid |
| `GET /merch/{product}` | Product | Size/color selectors, add to cart |
| `GET /cart`, `POST /cart/items`, `PATCH/DELETE /cart/items/{id}` | Cart | Session-based cart |
| `POST /checkout` | Checkout | Creates `orders` (pending) → Stripe Checkout Session → redirect |
| `GET /checkout/success`, `GET /checkout/cancel` | | Success clears cart, shows order number |
| `POST /stripe/webhook` | | `checkout.session.completed` → mark order (merch or camp) paid, email receipt (CSRF-exempt, signature-verified) |
| `GET /contact`, `POST /contact` | Contact | Form (name, email, phone, subject, message) + phone/email display; rate-limited, honeypot |

### Admin (`/admin`, auth + verified)
| Section | Actions |
|---|---|
| Dashboard | Counts: open tryouts/camps, new registrations, unread contact messages, orders awaiting fulfillment |
| Teams, Coaches, Facility Photos, Products (+ variants) | Full CRUD, image upload, sort order, publish toggle |
| Camps | CRUD incl. price, capacity, registration window, YouTube URL; registrations list per camp with payment status, CSV export, refund link to Stripe |
| Tryouts | CRUD incl. registration window; registrations list per tryout, CSV export |
| Orders | List/filter by status, view detail, mark fulfilled/cancelled, link to Stripe dashboard |
| Contact Submissions | List, view, mark read |
| Site Settings *(admin only)* | Form for home/facility/contact copy, YouTube embeds |
| Users & Invitations *(admin only)* | Invite by email with role, resend/revoke invites, change role, deactivate user |
| SEO & Sharing | Sitewide defaults (title template, default description, default share image, organization schema fields); per-page editor for every static page; "SEO & Sharing" tab on Team/Coach/Camp/Tryout/Product edit forms for per-record overrides; live Google result + Facebook/iMessage card previews; character-count guidance; "Re-scrape" links |

---

## 5. Design System

Palette mapped to Tailwind v4 theme tokens in `resources/css/app.css`:

| Token | Hex | Use |
|---|---|---|
| `--color-background` | `#FEFEFE` | Page background |
| `--color-muted` | `#B7B6B4` | Borders, muted text, dividers |
| `--color-primary` | `#6D96B6` | Buttons, links, accents, CTA |
| `--color-navy` / `--color-foreground` | `#161A35` | Headings, body text, header/footer backgrounds |

Public site: navy header/footer, white content, sky-blue CTAs. Admin: keep the existing starter-kit shell, re-tinted to the palette. Mobile-first — parents will mostly visit on phones.

---

## 5b. SEO & GEO (Generative Engine Optimization)

**Goal:** rank in Google for local queries ("travel baseball [town]", "baseball tryouts near me") *and* be the source AI assistants (ChatGPT, Gemini, Perplexity, Google AI Overviews) cite when someone asks about youth travel baseball in the area.

**Managed per page (admin → SEO):**
- `<title>`, meta description, canonical, robots (`index,follow` / `noindex`)
- Social share card (see below) — separate title/description/image from search meta
- Optional JSON-LD override for power users; otherwise auto-generated

**Social sharing cards (Open Graph / Twitter / iMessage / Facebook Messenger / GroupMe):**

This is how parents actually pass the site around — a tryout or camp link dropped in a group text or Facebook group. Every shareable URL renders a complete card:

- **Tags emitted server-side on every page:** `og:type` (`website` / `article` / `event`-style data via JSON-LD), `og:title`, `og:description`, `og:url` (canonical), `og:image` + `og:image:width/height/alt`, `og:site_name`, `og:locale`, `twitter:card` (`summary_large_image`), `twitter:title/description/image`, `fb:app_id` (optional, from settings)
- **Manageable per record (Tryout, Camp, Team, Product, Coach) and per static page:** share title, share description, share image (upload), image alt text — an "Share preview" panel on the edit form renders a live mock of the Facebook/iMessage card so staff see exactly what parents will see before publishing
- **Image handling:** uploads run through the `Image` facade → `cover(1200, 630)` webp/jpg at ≤ 300 KB, stored publicly with an absolute URL (OG requires absolute). Rejects images under 600×315 with a helpful message
- **Fallback chain (so nothing ever shares as a blank card):** record's share image → record's primary photo (auto-cropped to 1200×630) → page-level share image → sitewide default share image (logo on navy `#161A35`)
- **Text fallback:** Tryout: "*[Division] Tryouts — [Mon DD, YYYY · h:mm A] · [Location]*" / "Registration open until [date]. Register now." Camp: name + dates + location + price. Team: name + division + season. Product: name + price
- **Cache-busting:** OG image URLs carry a version query (`?v=<updated_at>`) so Facebook/iMessage pick up a swapped image; admin "Re-scrape" button links to Facebook Sharing Debugger and LinkedIn Post Inspector for the URL
- **Share buttons** on Tryout, Camp, and Product pages: native Web Share API on mobile (opens the phone's share sheet → Messages/WhatsApp/GroupMe), with Facebook / X / copy-link fallbacks on desktop
- **Tests:** feature tests assert the rendered HTML for a tryout page contains the expected `og:*`/`twitter:*` tags, the fallback chain resolves in the right order, and uploaded share images are cropped to 1200×630

**Auto-generated structured data (JSON-LD), rendered server-side:**
| Page | Schema |
|---|---|
| Sitewide | `SportsOrganization` (+ `LocalBusiness` fields: address, geo, phone, email, sameAs socials, areaServed) |
| Home | `WebSite` + `Organization`, optional `FAQPage` from an admin-managed FAQ list |
| Teams | `SportsTeam` per team (name, sport, ageRange, coach as `Person`) |
| Coaches | `Person` (name, jobTitle, worksFor) |
| Facility | `SportsActivityLocation` (address, geo, photos, openingHours if applicable) |
| Camps / Tryouts | `Event` (`SportsEvent`) with startDate/endDate, location, offers (price / free), eventStatus — makes them eligible for Google event rich results |
| Merch | `Product` + `Offer` (price, availability) |
| All | `BreadcrumbList` |

**Technical SEO / GEO checklist (Phase 5):**
- SSR on; semantic HTML (one `h1`, real headings, descriptive alt text from admin-entered captions)
- `sitemap.xml` (dynamic: static pages + published teams/camps/tryouts/products) and `robots.txt` that allows AI crawlers (GPTBot, ClaudeBot, PerplexityBot, Google-Extended) — GEO depends on *not* blocking them
- `llms.txt` at the root: concise plain-text summary of the org, programs, location, contact — the emerging convention AI crawlers read
- Clean, stable URLs (`/tryouts/2027-13u-tryouts`), 301s if slugs change
- Image optimization (webp via `Image` facade), lazy loading, Core Web Vitals pass
- Answer-shaped content on key pages: a short direct summary paragraph at the top of Home, Teams, Tryouts, Facility ("Eagles Baseball Travel is a youth travel baseball organization in [town], IL offering 9U–17U teams…") — this is what LLMs quote
- Google Business Profile + consistent NAP (name/address/phone) across site and listings (offline task, but the site's `LocalBusiness` schema must match it exactly)
- Google Search Console + Bing Webmaster (Bing feeds ChatGPT/Copilot) verification meta tags manageable in admin

---

## 6. Phases

**Phase 0 — Foundation (½ day)**
- Palette + fonts in `app.css`; `PublicLayout.vue` (nav, footer with contact info); disable public registration; `role` on users + `invitations` + invite flow + role gate/policies; seed your admin user; Resend mailer config (`resend/resend-php`); `storage:link`; `ImageUpload` service on the `Image` facade (full-size webp + thumbnail); admin sidebar nav entries; enable Inertia SSR; `useSeo()` composable + `SeoMeta` model/`seo_meta` table + `SeoResolver` service (page/record meta → defaults fallback → JSON-LD).

**Phase 1 — Core content managers (2–3 days)**
- Migrations/models/factories/seeders: `teams`, `coaches`, `camps`, `facility_photos`, `site_settings`.
- Admin CRUD for each + Site Settings form + SEO & Sharing section (sitewide defaults incl. default share image, per-page editor, per-record "SEO & Sharing" tab with live card preview, share-image upload with 1200×630 crop).
- Public pages: Home, Teams, Facility, Camps, Coaches.
- Pest tests for each resource.

**Phase 2 — Tryouts (free registration) (1–2 days)**
- `tryouts` + `tryout_registrations`; admin CRUD + registrations view/export.
- Public tryouts page + registration form with window/capacity enforcement; confirmation + org notification emails.
- Share cards + share buttons on tryout pages (auto-generated title/description from division/date/location, share-image fallback chain); same for camps.
- Tests: window open/closed/upcoming, capacity reached, validation.

**Phase 3 — Contact (½ day)**
- `contact_submissions`, public form (rate limit + honeypot), org email, admin inbox.

**Phase 4 — Stripe: Merch + Camp registration (4–5 days)** *(`stripe/stripe-php`; your Stripe test keys in `.env`)*
- `PaymentService` + webhook handler shared by both flows.
- Merch: `products`, `product_variants`, `orders`, `order_items`; admin product/variant CRUD; session cart; checkout → Stripe Checkout Session; success/cancel pages; order emails; admin orders view.
- Camps: `camp_registrations`; public registration form → pending order → Stripe → paid; capacity holds with auto-expiry of unpaid registrations; admin registrations list/export.
- Tests: cart ops, order creation, webhook marks merch/camp paid, capacity + window enforcement, guest checkout validation.
- Launch: org supplies live Stripe keys → swap `.env`, register production webhook.

**Phase 5 — Polish & launch (1–2 days)**
- Full §5b checklist: JSON-LD for all types, `sitemap.xml`, `robots.txt`, `llms.txt`, breadcrumbs, answer-shaped intro copy, Search Console/Bing verification, 404 page, image optimization, accessibility pass, Lighthouse, production env (mail driver, queue worker, Stripe live keys, webhook endpoint), backups.

**Optional later:** generic `pages` table for one-off pages; camp registration form (mirroring tryouts); customer accounts / order lookup; newsletter signup.

---

## 7. Decisions (answered) & Remaining Inputs

| # | Topic | Decision |
|---|---|---|
| 1 | Stripe | Build against your **test** account; org supplies live keys at launch (`.env` swap only) |
| 2 | Camps | Register on the site → Stripe Checkout for payment (shares merch payment flow) |
| 3 | Tryouts | Free — registration form only, no payment |
| 4 | Teams | Cards only, no detail pages |
| 5 | Media | Modest photo volume → local `public` disk; video via YouTube URL embeds, never uploaded |
| 6 | Email | Resend (`resend/resend-php`, built-in Laravel driver) |
| 7 | Admins | Multiple staff, invite-only; roles: **admin** (you) and **staff** |
| 8 | Content | None yet → seed with clearly-marked placeholder copy/images; everything editable in admin |
| 9 | SEO/GEO location | Needed but unknown → schema fields built and left blank; site works without them, `LocalBusiness` schema activates once address/geo are entered |

**Still needed from you (none block starting):**
- Stripe test keys (`STRIPE_KEY`, `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET`) — Phase 4
- Resend API key + sending domain — needed for real email; `log` driver until then
- Camp registration fields beyond the defaults (player/parent info, emergency contact, medical notes) and whether camps need waivers
- Logo, photos, copy, facility address/service area, social URLs — whenever available

**Dependencies to add (approved by these answers):** `stripe/stripe-php`, `resend/resend-php`.

---

## 8. Build Status (2026-08-16)

**Phases 0–4 built and verified.** 233 Pest tests passing (1,589 assertions), `vue-tsc` + ESLint + Pint clean, production `npm run build:ssr` succeeds, SSR verified serving meta/JSON-LD in initial HTML.

**Run locally**
```bash
composer run dev            # Laravel + Vite (SSR automatic in dev) + queue
php artisan migrate:fresh --seed   # admin: jlohr@autorisknow.com / password  ·  staff: staff@example.com / password
```
Dev seed data (teams, coaches, tryouts, camps, products, orders, contact messages, placeholder facility photos) only seeds in `APP_ENV=local`.

**Env to fill in**
- `STRIPE_KEY`, `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET` (test keys now; org's live keys at launch). Without them a FakeGateway is used locally. Webhook endpoint: `POST /stripe/webhook`.
- `RESEND_API_KEY` + `MAIL_MAILER=resend` (currently `log`).
- Production: `php artisan inertia:start-ssr` (or the Vite plugin's SSR daemon), `queue:work`, and `schedule:run` (runs `camps:expire-pending` every 15 min).

**Dependencies added:** `stripe/stripe-php`, `resend/resend-php`, `intervention/image` (required driver for Laravel 13's first-party `Image` facade).

**Assumptions baked in (all easy to change):** merch shipping cost = $0 (`CreateMerchOrder::SHIPPING_FLAT_CENTS`); unpaid camp registration hold = 30 min; contact/registration forms use a honeypot + throttle (no CAPTCHA); tryout positions list P/C/1B/2B/SS/3B/OF/UTIL; org address / geo / socials blank until provided (LocalBusiness schema activates automatically once entered under Admin → Site Settings → Organization).

**Phase 5 remaining (launch polish):** logo + real photos/copy, Google Business Profile + Search Console/Bing verification (fields exist in Admin → Site Settings → SEO), custom 404 page, Lighthouse/a11y pass, production hosting + Resend domain + Stripe live keys.
