<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Camp;
use App\Models\Product;
use App\Models\Tryout;
use App\Services\PageVisibility;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class SitemapController extends Controller
{
    public const string CACHE_KEY = 'sitemap.xml';

    public function __construct(private PageVisibility $pages) {}

    public function __invoke(): Response
    {
        $xml = Cache::remember(self::CACHE_KEY, now()->addHour(), fn () => $this->build());

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    private function build(): string
    {
        $urls = collect($this->staticUrls());

        if ($this->pages->isEnabled('camps')) {
            Camp::query()->published()->get(['slug', 'updated_at'])->each(fn (Camp $camp) => $urls->push([
                'loc' => route('camps.show', $camp), 'lastmod' => $camp->updated_at?->toAtomString(), 'priority' => '0.7', 'changefreq' => 'weekly',
            ]));
        }

        Tryout::query()->published()->get(['slug', 'updated_at'])->each(fn (Tryout $tryout) => $urls->push([
            'loc' => route('tryouts.show', $tryout), 'lastmod' => $tryout->updated_at?->toAtomString(), 'priority' => '0.8', 'changefreq' => 'weekly',
        ]));

        if ($this->pages->isEnabled('merch')) {
            Product::query()->active()->get(['slug', 'updated_at'])->each(fn (Product $product) => $urls->push([
                'loc' => route('merch.show', $product), 'lastmod' => $product->updated_at?->toAtomString(), 'priority' => '0.5', 'changefreq' => 'monthly',
            ]));
        }

        $entries = $urls->map(function (array $url): string {
            $parts = ['<loc>'.e($url['loc']).'</loc>'];

            if (! empty($url['lastmod'])) {
                $parts[] = '<lastmod>'.e($url['lastmod']).'</lastmod>';
            }

            $parts[] = '<changefreq>'.$url['changefreq'].'</changefreq>';
            $parts[] = '<priority>'.$url['priority'].'</priority>';

            return '  <url>'.implode('', $parts).'</url>';
        })->implode("\n");

        return "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n{$entries}\n</urlset>\n";
    }

    /**
     * The static public pages, minus any switched off in Site Settings — those 404,
     * so they must not be advertised.
     *
     * @return list<array<string, string>>
     */
    private function staticUrls(): array
    {
        $pages = [
            ['route' => 'home', 'priority' => '1.0', 'changefreq' => 'weekly'],
            ['route' => 'teams.index', 'priority' => '0.8', 'changefreq' => 'monthly'],
            ['route' => 'facility', 'priority' => '0.6', 'changefreq' => 'monthly'],
            ['route' => 'coaches.index', 'priority' => '0.6', 'changefreq' => 'monthly'],
            ['route' => 'camps.index', 'priority' => '0.8', 'changefreq' => 'weekly'],
            ['route' => 'tryouts.index', 'priority' => '0.9', 'changefreq' => 'weekly'],
            ['route' => 'merch.index', 'priority' => '0.6', 'changefreq' => 'weekly'],
            ['route' => 'contact', 'priority' => '0.5', 'changefreq' => 'yearly'],
        ];

        $urls = [];

        foreach ($pages as $page) {
            if (! $this->pages->allowsRoute($page['route'])) {
                continue;
            }

            $urls[] = [
                'loc' => route($page['route']),
                'priority' => $page['priority'],
                'changefreq' => $page['changefreq'],
            ];
        }

        return $urls;
    }
}
