<?php

namespace App\Http\Middleware;

use App\Services\PageVisibility;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 404s a public page that has been switched off in Site Settings, unless this
 * session opened the page's preview link.
 */
class EnsurePageIsEnabled
{
    /**
     * Request attribute set while a switched-off page is shown as a preview.
     */
    public const string PREVIEW_ATTRIBUTE = 'page_preview';

    public function __construct(private PageVisibility $pages) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string $page): Response
    {
        if ($this->pages->isEnabled($page)) {
            return $next($request);
        }

        abort_unless($this->pages->isPreviewing($request->session(), $page), 404);

        $request->attributes->set(self::PREVIEW_ATTRIBUTE, PageVisibility::PAGES[$page]['label']);

        $response = $next($request);
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');

        return $response;
    }
}
