<?php

namespace App\Actions\Seo;

use App\Models\SeoMeta;
use App\Services\ImageUploader;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;

/**
 * Create/update the SeoMeta row for a model record or a static route key
 * from the validated `seo` form section, including the share image upload.
 */
class SyncSeoMeta
{
    public function __construct(private ImageUploader $images) {}

    /**
     * @param  array<string, mixed>|null  $seo
     */
    public function forModel(Model $model, ?array $seo, ?UploadedFile $shareImage = null): ?SeoMeta
    {
        /** @var SeoMeta $meta */
        $meta = $model->seoMeta()->firstOrNew();

        return $this->apply($meta, $seo, $shareImage);
    }

    /**
     * @param  array<string, mixed>|null  $seo
     */
    public function forRoute(string $routeKey, ?array $seo, ?UploadedFile $shareImage = null): ?SeoMeta
    {
        $meta = SeoMeta::query()->firstOrNew(['route_key' => $routeKey]);

        return $this->apply($meta, $seo, $shareImage);
    }

    /**
     * @param  array<string, mixed>|null  $seo
     */
    private function apply(SeoMeta $meta, ?array $seo, ?UploadedFile $shareImage): ?SeoMeta
    {
        $seo ??= [];

        foreach (SeoMeta::EDITABLE as $field) {
            if ($field === 'json_ld') {
                continue;
            }

            if (array_key_exists($field, $seo)) {
                $meta->{$field} = filled($seo[$field]) ? $seo[$field] : null;
            }
        }

        if (! empty($seo['remove_share_image']) && $meta->share_image_path) {
            $this->images->deleteShareImage($meta->share_image_path);
            $meta->share_image_path = null;
        }

        if ($shareImage) {
            $meta->share_image_path = $this->images->replaceShareImage($shareImage, $meta->share_image_path);
        }

        $meta->save();

        return $meta;
    }
}
