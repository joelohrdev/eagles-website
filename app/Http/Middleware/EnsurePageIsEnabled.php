<?php

namespace App\Http\Middleware;

use App\Services\PageVisibility;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 404s a public page that has been switched off in Site Settings.
 */
class EnsurePageIsEnabled
{
    public function __construct(private PageVisibility $pages) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string $page): Response
    {
        abort_unless($this->pages->isEnabled($page), 404);

        return $next($request);
    }
}
