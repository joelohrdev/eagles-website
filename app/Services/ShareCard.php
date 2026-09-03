<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\PngEncoder;
use Intervention\Image\ImageManager;
use Intervention\Image\Typography\FontFactory;

/**
 * Renders the site's default social share card: the logo above the
 * organization name on the brand navy, at the 1200×630 card size.
 *
 * Used for og:image whenever a page has no image of its own and no default
 * has been uploaded in Site Settings, so shared links always show something.
 * The result is cached on the public disk under a fingerprint of everything
 * that shapes it, so it re-renders by itself when the org name or logo changes.
 */
class ShareCard
{
    public const string DISK = 'public';

    public const string DIRECTORY = 'share/default';

    public const int WIDTH = 1200;

    public const int HEIGHT = 630;

    private const string BACKGROUND = '#161A35';

    private const string FOREGROUND = '#FEFEFE';

    private const string LOGO = 'eagles-logo.png';

    private const int LOGO_WIDTH = 300;

    private const int GAP = 40;

    private const int MAX_TEXT_WIDTH = 1000;

    private const int MAX_FONT_SIZE = 78;

    private const int MIN_FONT_SIZE = 30;

    /** Bump to re-render every card after a design change. */
    private const int VERSION = 1;

    public function __construct(private SiteSettings $settings) {}

    /**
     * Path to the rendered card on the public disk, rendering it if needed.
     */
    public function path(): string
    {
        $path = self::DIRECTORY.'/'.$this->fingerprint().'.png';

        if (! Storage::disk(self::DISK)->exists($path)) {
            $this->render($path);
        }

        return $path;
    }

    public function url(): string
    {
        return (string) ImageUploader::url($this->path());
    }

    /**
     * Draw the card and replace any previously rendered version.
     */
    private function render(string $path): void
    {
        $manager = ImageManager::usingDriver(new Driver);
        $card = $manager->createImage(self::WIDTH, self::HEIGHT)->fill(self::BACKGROUND);

        $name = strtoupper((string) $this->settings->get('org_name'));
        $fontSize = $this->fontSizeFor($name);
        $textHeight = (int) round($fontSize * 0.75);

        $logo = $this->logoPath()
            ? $manager->decodePath($this->logoPath())->scale(width: self::LOGO_WIDTH)
            : null;

        $blockHeight = ($logo ? $logo->height() + self::GAP : 0) + $textHeight;
        $top = (int) round((self::HEIGHT - $blockHeight) / 2);

        if ($logo) {
            $card->insert($logo, 0, $top, 'top');
        }

        $card->text($name, (int) (self::WIDTH / 2), $top + $blockHeight - $textHeight, function (FontFactory $font) use ($fontSize): void {
            $font->filename($this->fontPath());
            $font->size($fontSize);
            $font->color(self::FOREGROUND);
            $font->align('center', 'top');
        });

        foreach (Storage::disk(self::DISK)->files(self::DIRECTORY) as $stale) {
            Storage::disk(self::DISK)->delete($stale);
        }

        Storage::disk(self::DISK)->put($path, (string) $card->encode(new PngEncoder), 'public');
    }

    /**
     * Largest size that keeps the name inside the card's margins.
     */
    private function fontSizeFor(string $text): int
    {
        for ($size = self::MAX_FONT_SIZE; $size > self::MIN_FONT_SIZE; $size -= 2) {
            $box = imagettfbbox($size, 0, $this->fontPath(), $text);

            if ($box !== false && ($box[2] - $box[0]) <= self::MAX_TEXT_WIDTH) {
                return $size;
            }
        }

        return self::MIN_FONT_SIZE;
    }

    /**
     * Everything that changes the rendered card.
     */
    private function fingerprint(): string
    {
        return substr(md5(implode('|', [
            self::VERSION,
            (string) $this->settings->get('org_name'),
            (string) (($this->logoPath() ? filemtime($this->logoPath()) : 0)),
        ])), 0, 16);
    }

    private function logoPath(): ?string
    {
        $path = public_path(self::LOGO);

        return is_file($path) ? $path : null;
    }

    private function fontPath(): string
    {
        return resource_path('fonts/BarlowCondensed-Bold.ttf');
    }
}
