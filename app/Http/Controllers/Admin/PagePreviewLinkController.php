<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\PageVisibility;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class PagePreviewLinkController extends Controller
{
    /**
     * Replace a page's preview link; anyone holding the old one loses access.
     */
    public function update(PageVisibility $pages, string $page): RedirectResponse
    {
        $pages->resetPreviewLink($page);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('New preview link created. The old link no longer works.')]);

        return to_route('admin.settings.edit', 'pages');
    }
}
