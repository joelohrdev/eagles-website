<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Image;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Stores uploaded images on the public disk as optimized webp files.
 *
 * Every image is stored as a full-size version plus a derived thumbnail
 * (same file name inside a `thumbs/` sub-directory), so models only need
 * to persist the full-size path. Share images (Open Graph) are stored
 * separately at the fixed 1200×630 card size.
 */
class ImageUploader
{
    public const string DISK = 'public';

    public const int MAX_WIDTH = 1600;

    public const int THUMB_WIDTH = 600;

    public const int THUMB_HEIGHT = 400;

    public const int SHARE_WIDTH = 1200;

    public const int SHARE_HEIGHT = 630;

    /**
     * Store a full-size webp plus a thumbnail. Returns the full-size path.
     */
    public function store(UploadedFile $file, string $directory): string
    {
        $name = Str::random(40).'.webp';

        Image::fromUpload($file)
            ->orient()
            ->scale(width: self::MAX_WIDTH)
            ->optimize('webp')
            ->storePubliclyAs($directory, $name, self::DISK);

        Image::fromUpload($file)
            ->orient()
            ->cover(self::THUMB_WIDTH, self::THUMB_HEIGHT)
            ->optimize('webp')
            ->storePubliclyAs("{$directory}/thumbs", $name, self::DISK);

        return "{$directory}/{$name}";
    }

    /**
     * Store an image cropped to the social share card size (1200×630).
     */
    public function storeShareImage(UploadedFile $file, string $directory = 'share'): string
    {
        $name = Str::random(40).'.jpg';

        Image::fromUpload($file)
            ->orient()
            ->cover(self::SHARE_WIDTH, self::SHARE_HEIGHT)
            ->optimize('jpg', 82)
            ->storePubliclyAs($directory, $name, self::DISK);

        return "{$directory}/{$name}";
    }

    /**
     * Replace an existing image (deleting the old files) and return the new path.
     */
    public function replace(UploadedFile $file, string $directory, ?string $existingPath): string
    {
        $this->delete($existingPath);

        return $this->store($file, $directory);
    }

    public function replaceShareImage(UploadedFile $file, ?string $existingPath, string $directory = 'share'): string
    {
        $this->deleteShareImage($existingPath);

        return $this->storeShareImage($file, $directory);
    }

    public function delete(?string $path): void
    {
        if (blank($path)) {
            return;
        }

        Storage::disk(self::DISK)->delete([$path, self::thumbnailPath($path)]);
    }

    public function deleteShareImage(?string $path): void
    {
        if (blank($path)) {
            return;
        }

        Storage::disk(self::DISK)->delete($path);
    }

    public static function thumbnailPath(string $path): string
    {
        $dir = Str::beforeLast($path, '/');
        $name = Str::afterLast($path, '/');

        return $dir === $path ? "thumbs/{$name}" : "{$dir}/thumbs/{$name}";
    }

    /**
     * Public URL for a stored path, or null. Root-relative unless the disk
     * is pointed at a CDN, so it resolves against whatever host serves the app.
     */
    public static function url(?string $path): ?string
    {
        return blank($path) ? null : Storage::disk(self::DISK)->url($path);
    }

    public static function thumbnailUrl(?string $path): ?string
    {
        return blank($path) ? null : Storage::disk(self::DISK)->url(self::thumbnailPath($path));
    }

    /**
     * Fully qualified URL for consumers outside the page (og:image, JSON-LD, mail).
     */
    public static function absoluteUrl(?string $path): ?string
    {
        $url = self::url($path);

        return $url === null ? null : url($url);
    }
}
