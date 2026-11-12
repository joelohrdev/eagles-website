<?php

namespace App\Models;

use App\Services\ImageUploader;
use Database\Factories\CampCoachFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A coach shown on one camp's page. Separate from the site's coaching staff
 * (Coach) because camps often bring in guest instructors. `position` is the
 * order from the camp form's coaches list.
 *
 * @property int $id
 * @property int $camp_id
 * @property string $name
 * @property string|null $title Role at this camp, e.g. Pitching Instructor.
 * @property string|null $bio
 * @property string|null $photo_path
 * @property int $position
 */
#[Fillable(['camp_id', 'name', 'title', 'bio', 'photo_path', 'position'])]
class CampCoach extends Model
{
    /** @use HasFactory<CampCoachFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $appends = ['photo_url'];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Camp, $this>
     */
    public function camp(): BelongsTo
    {
        return $this->belongsTo(Camp::class);
    }

    /**
     * @return Attribute<string|null, never>
     */
    protected function photoUrl(): Attribute
    {
        return Attribute::make(get: fn (): ?string => ImageUploader::url($this->photo_path));
    }
}
