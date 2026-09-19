<?php

namespace Database\Seeders;

use App\Services\ImageUploader;
use App\Services\SiteSettings;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Loads the home page snapshot written by `php artisan home:export` into
 * site settings, copying its images onto the public disk. Safe to re-run.
 */
class HomeContentSeeder extends Seeder
{
    public const string SETTINGS_FILE = 'settings.json';

    public const string IMAGES_DIRECTORY = 'images';

    public function __construct(private SiteSettings $settings) {}

    public static function contentPath(): string
    {
        return database_path('seeders/content/home');
    }

    public function run(): void
    {
        $path = self::contentPath();

        /** @var array<string, mixed> $values */
        $values = File::json($path.'/'.self::SETTINGS_FILE, flags: JSON_THROW_ON_ERROR);
        $values = array_intersect_key($values, array_flip(SiteSettings::GROUPS['home']));

        foreach ($values as $key => $image) {
            if (! in_array($key, SiteSettings::IMAGE_KEYS, true) || ! is_string($image) || $image === '') {
                continue;
            }

            $source = $path.'/'.self::IMAGES_DIRECTORY.'/'.$image;

            if (! File::exists($source)) {
                throw new RuntimeException("Home content image [{$source}] is missing. Re-run `php artisan home:export`.");
            }

            Storage::disk(ImageUploader::DISK)->put($image, File::get($source));

            $thumbnail = ImageUploader::thumbnailPath($image);

            if (File::exists($path.'/'.self::IMAGES_DIRECTORY.'/'.$thumbnail)) {
                Storage::disk(ImageUploader::DISK)->put($thumbnail, File::get($path.'/'.self::IMAGES_DIRECTORY.'/'.$thumbnail));
            }
        }

        $this->settings->setMany($values);
    }
}
