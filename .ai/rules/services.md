---
paths:
  - 'app/Services/**'
---

# Services

## Images go through ImageUploader (Laravel Image facade), never Spatie
Use App\Services\ImageUploader for every upload: `store($file, 'dir')` writes `dir/{hash}.webp` (max 1600w) plus `dir/thumbs/{hash}.webp` (600×400 cover) on the `public` disk and returns the full-size path; models persist only that path and expose `*_url` / `*_thumbnail_url` accessors. Share/OG images use `storeShareImage()` (1200×630 jpg). Video is never uploaded — models store a `youtube_url`. Payments: bind-time singleton `App\Contracts\PaymentGateway` resolves to FakeGateway when STRIPE_SECRET is blank or under tests, StripeGateway otherwise; webhook is CSRF-exempt at /stripe/webhook.

## Image URLs are host-relative; use absoluteUrl() for off-page consumers
The `public` disk has no `url` configured (only the optional FILESYSTEM_PUBLIC_URL for a CDN), so `ImageUploader::url()` / `thumbnailUrl()` return root-relative `/storage/...` paths that resolve against whatever host serves the app. This is deliberate: hard-coding APP_URL broke every uploaded image whenever the site was browsed on a host other than APP_URL (e.g. Herd's eagles.test while APP_URL said localhost:8000) — images rendered broken right after upload.

Anything consumed outside the page — og:image in SeoResolver, JSON-LD `image`/`logo` in App\Support\Seo\Schema, mail — must use `ImageUploader::absoluteUrl()`, which wraps the relative path in `url()`. Inertia props for in-page display stay relative.

## Default share card is generated, not uploaded
Every page has an og:image. The chain in SeoResolver is: the page's stored SeoMeta share image → the controller's default (usually the record's own photo) → the `seo_default_share_image` site setting → `route('share-card')`.

That last one is `App\Services\ShareCard`: a 1200×630 PNG drawn with Intervention (navy #161A35, public/eagles-logo.png above the uppercase org name in resources/fonts/BarlowCondensed-Bold.ttf, SIL OFL, committed with its OFL.txt). It caches to the public disk at share/default/{fingerprint}.png, where the fingerprint covers the org name, the logo's mtime, and a VERSION constant — change any of those and it re-renders and deletes the stale file. Bump VERSION after a design change.

`/share-card.png` (ShareCardController) is the stable URL and redirects to the uploaded site default when one exists, so it always means "the site's default card" — the admin preview in SeoFields.vue points at it, which keeps the preview honest.

Text needs a real TTF: GD/Imagick cannot use the woff2 files Vite emits, which is why the font is committed under resources/fonts.
