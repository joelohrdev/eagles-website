<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Seo\SyncSeoMeta;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReorderTeamsRequest;
use App\Http\Requests\Admin\StoreTeamRequest;
use App\Http\Requests\Admin\UpdateTeamRequest;
use App\Models\Coach;
use App\Models\Team;
use App\Services\ImageUploader;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class TeamController extends Controller
{
    public function __construct(
        private ImageUploader $images,
        private SyncSeoMeta $syncSeoMeta,
    ) {}

    public function index(): Response
    {
        return Inertia::render('admin/teams/Index', [
            'teams' => Team::query()
                ->with('coach:id,name')
                ->ordered()
                ->paginate(20)
                ->withQueryString(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/teams/Create', [
            'coaches' => $this->coachOptions(),
        ]);
    }

    public function store(StoreTeamRequest $request): RedirectResponse
    {
        $team = new Team($this->teamAttributes($request->validated()));
        $team->sort_order = Team::nextSortOrder();

        if ($request->hasFile('photo')) {
            $team->photo_path = $this->images->store($request->file('photo'), 'teams');
        }

        $team->save();

        $this->syncSeoMeta->forModel($team, $request->validated('seo'), $request->file('seo_share_image'));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Team created.')]);

        return to_route('admin.teams.index');
    }

    public function edit(Team $team): Response
    {
        $team->load('seoMeta');

        return Inertia::render('admin/teams/Edit', [
            'team' => $team,
            'seo' => $team->seoMeta,
            'coaches' => $this->coachOptions(),
        ]);
    }

    public function update(UpdateTeamRequest $request, Team $team): RedirectResponse
    {
        $team->fill($this->teamAttributes($request->validated()));

        if ($request->boolean('remove_photo') && $team->photo_path) {
            $this->images->delete($team->photo_path);
            $team->photo_path = null;
        }

        if ($request->hasFile('photo')) {
            $team->photo_path = $this->images->replace($request->file('photo'), 'teams', $team->photo_path);
        }

        $team->save();

        $this->syncSeoMeta->forModel($team, $request->validated('seo'), $request->file('seo_share_image'));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Team updated.')]);

        return to_route('admin.teams.edit', $team);
    }

    public function destroy(Team $team): RedirectResponse
    {
        $this->images->delete($team->photo_path);

        if ($team->seoMeta) {
            $this->images->deleteShareImage($team->seoMeta->share_image_path);
        }

        $team->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Team deleted.')]);

        return to_route('admin.teams.index');
    }

    public function reorder(ReorderTeamsRequest $request): RedirectResponse
    {
        Team::applyManualOrder($request->order());

        return back();
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function teamAttributes(array $validated): array
    {
        return [
            'name' => $validated['name'],
            'division' => $validated['division'],
            'season' => $validated['season'] ?? null,
            'description' => $validated['description'] ?? null,
            'coach_id' => $validated['coach_id'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ];
    }

    /**
     * @return EloquentCollection<int, Coach>
     */
    private function coachOptions(): EloquentCollection
    {
        return Coach::query()->ordered()->get(['id', 'name']);
    }
}
