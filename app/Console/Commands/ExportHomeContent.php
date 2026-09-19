<?php

namespace App\Console\Commands;

use App\Services\ImageUploader;
use App\Services\SiteSettings;
use Database\Seeders\HomeContentSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class ExportHomeContent extends Command
{
    protected $signature = 'home:export {--path= : Directory to write the snapshot to (defaults to the HomeContentSeeder content directory)}';

    protected $description = 'Snapshot the current home page settings and images so HomeContentSeeder can load them on another environment';

    public function handle(SiteSettings $settings): int
    {
        $path = $this->option('path') ?: HomeContentSeeder::contentPath();
        $imagesPath = $path.'/'.HomeContentSeeder::IMAGES_DIRECTORY;
        $disk = Storage::disk(ImageUploader::DISK);

        $values = $settings->group('home');

        File::ensureDirectoryExists($path);
        File::deleteDirectory($imagesPath);

        foreach (array_intersect(SiteSettings::IMAGE_KEYS, array_keys($values)) as $key) {
            $image = $values[$key];

            if (blank($image)) {
                continue;
            }

            if (! $disk->exists($image)) {
                $this->warn("Skipping {$key}: [{$image}] is not on the public disk.");
                $values[$key] = null;

                continue;
            }

            foreach ([$image, ImageUploader::thumbnailPath($image)] as $file) {
                if ($disk->exists($file)) {
                    File::ensureDirectoryExists(dirname($imagesPath.'/'.$file));
                    File::put($imagesPath.'/'.$file, (string) $disk->get($file));
                }
            }
        }

        File::put(
            $path.'/'.HomeContentSeeder::SETTINGS_FILE,
            json_encode($values, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR).PHP_EOL,
        );

        $this->info("Exported home page content to {$path}.");

        return self::SUCCESS;
    }
}
