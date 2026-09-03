<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Services\ImageUploader;
use App\Services\ShareCard;
use App\Services\SiteSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * The site's default share card at a stable URL, used for og:image when a page
 * has no image of its own. Serves the uploaded Site Settings default when there
 * is one, otherwise the generated logo-over-name card.
 */
class ShareCardController extends Controller
{
    public function __construct(private SiteSettings $settings, private ShareCard $card) {}

    public function __invoke(): BinaryFileResponse|RedirectResponse
    {
        $uploaded = $this->settings->get('seo_default_share_image');

        if (filled($uploaded)) {
            return redirect()->away((string) ImageUploader::absoluteUrl($uploaded));
        }

        return response()
            ->file(Storage::disk(ShareCard::DISK)->path($this->card->path()))
            ->setPublic()
            ->setMaxAge(86400);
    }
}
