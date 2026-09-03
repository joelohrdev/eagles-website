---
paths:
  - 'app/Http/Controllers/**'
---

# Controllers

## Public pages pass a `seo` prop; records use SyncSeoMeta
Every Site\* controller passes `'seo' => $seoResolver->forRoute('route.name', [...])->toArray()` (static pages) or `->forModel($record, [...])` (Team/Coach/Camp/Tryout/Product). Layers: stored SeoMeta row → controller defaults → SiteSettings seo_* defaults. JSON-LD builders live in App\Support\Seo\Schema; the Organization schema is always included. Admin forms include `App\Http\Requests\Concerns\HasSeoRules` and call `SyncSeoMeta::forModel()`; static pages are edited under Admin\SeoMetaController by route key. The `seo` prop is rendered by resources/js/components/site/SeoHead.vue inside PublicLayout — public pages must not set their own <Head> title.
