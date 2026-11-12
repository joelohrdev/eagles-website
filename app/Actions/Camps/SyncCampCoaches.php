<?php

namespace App\Actions\Camps;

use App\Models\Camp;
use App\Models\CampCoach;
use App\Services\ImageUploader;
use Illuminate\Http\UploadedFile;

/**
 * Save a camp's coaches from the camp form: rows with an id belonging to this
 * camp are updated, rows without one are created, rows left out are deleted
 * along with their photos. The order of the rows becomes the display order.
 */
class SyncCampCoaches
{
    public function __construct(private ImageUploader $images) {}

    /**
     * @param  list<array{id: int|null, name: string, title: string|null, bio: string|null, photo: UploadedFile|null, remove_photo: bool}>  $rows
     */
    public function handle(Camp $camp, array $rows): void
    {
        $existing = $camp->coaches()->get()->keyBy('id');
        $keptIds = [];

        foreach ($rows as $position => $row) {
            /** @var CampCoach $coach */
            $coach = $existing->get($row['id']) ?? new CampCoach(['camp_id' => $camp->id]);
            $coach->fill([
                'name' => $row['name'],
                'title' => $row['title'],
                'bio' => $row['bio'],
                'position' => $position,
            ]);

            if ($row['photo'] !== null) {
                $coach->photo_path = $this->images->replace($row['photo'], 'camp-coaches', $coach->photo_path);
            } elseif ($row['remove_photo'] && $coach->photo_path) {
                $this->images->delete($coach->photo_path);
                $coach->photo_path = null;
            }

            $coach->save();
            $keptIds[] = $coach->id;
        }

        $this->delete($existing->except($keptIds)->all());
    }

    /**
     * Delete coaches and their photos (also used when a camp is deleted).
     *
     * @param  iterable<CampCoach>  $coaches
     */
    public function delete(iterable $coaches): void
    {
        foreach ($coaches as $coach) {
            $this->images->delete($coach->photo_path);
            $coach->delete();
        }
    }
}
