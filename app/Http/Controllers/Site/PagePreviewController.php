<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Services\PageVisibility;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Opens a switched-off page from the signed preview link an admin shared, so
 * the page can be reviewed before it goes live.
 */
class PagePreviewController extends Controller
{
    public function __invoke(Request $request, PageVisibility $pages, string $page, int $version): RedirectResponse
    {
        if (! $pages->isEnabled($page)) {
            abort_unless($pages->grantPreview($request->session(), $page, $version), 404);
        }

        return redirect()->to(PageVisibility::url($page));
    }
}
