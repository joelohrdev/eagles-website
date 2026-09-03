<?php

namespace App\Models;

use App\Services\ImageUploader;
use Database\Factories\SeoMetaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int $id
 * @property string|null $route_key
 * @property string|null $metable_type
 * @property int|null $metable_id
 * @property string|null $title
 * @property string|null $description
 * @property string|null $canonical_url
 * @property string|null $robots
 * @property string|null $share_title
 * @property string|null $share_description
 * @property string|null $share_image_path
 * @property string|null $share_image_alt
 * @property string|null $twitter_card
 * @property array<string, mixed>|null $json_ld
 */
#[Fillable(['route_key', 'title', 'description', 'canonical_url', 'robots', 'share_title', 'share_description', 'share_image_path', 'share_image_alt', 'twitter_card', 'json_ld'])]
class SeoMeta extends Model
{
    /** @use HasFactory<SeoMetaFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $appends = ['share_image_url'];

    /**
     * Fields staff can edit through the SEO & Sharing form.
     *
     * @var list<string>
     */
    public const array EDITABLE = ['title', 'description', 'canonical_url', 'robots', 'share_title', 'share_description', 'share_image_alt', 'twitter_card', 'json_ld'];

    /**
     * @return Attribute<string|null, never>
     */
    protected function shareImageUrl(): Attribute
    {
        return Attribute::make(get: fn (): ?string => ImageUploader::url($this->share_image_path));
    }

    protected function casts(): array
    {
        return [
            'json_ld' => 'array',
        ];
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function metable(): MorphTo
    {
        return $this->morphTo();
    }
}
