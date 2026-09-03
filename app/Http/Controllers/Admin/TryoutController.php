<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Seo\SyncSeoMeta;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTryoutRequest;
use App\Http\Requests\Admin\UpdateTryoutRequest;
use App\Models\Tryout;
use App\Services\ImageUploader;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class TryoutController extends Controller
{
    public function __construct(
        private ImageUploader $images,
        private SyncSeoMeta $syncSeoMeta,
    ) {}

    public function index(): Response
    {
        $tryouts = Tryout::query()
            ->withCount('registrations')
            ->orderByDesc('event_at')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Tryout $tryout) => [
                ...$tryout->toArray(),
                'registration_state' => $tryout->registrationState(),
                'spots_remaining' => $tryout->spotsRemaining(),
            ]);

        return Inertia::render('admin/tryouts/Index', [
            'tryouts' => $tryouts,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/tryouts/Create');
    }

    public function store(StoreTryoutRequest $request): RedirectResponse
    {
        $data = $request->tryoutData();

        if ($request->hasFile('image')) {
            $data['image_path'] = $this->images->store($request->file('image'), 'tryouts');
        }

        $tryout = Tryout::create($data);

        $this->syncSeoMeta->forModel($tryout, $request->validated('seo'), $request->file('seo_share_image'));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Tryout created.')]);

        return to_route('admin.tryouts.edit', $tryout);
    }

    public function edit(Tryout $tryout): Response
    {
        $tryout->loadCount('registrations');

        return Inertia::render('admin/tryouts/Edit', [
            'tryout' => $tryout,
            'seo' => $tryout->seoMeta,
            'publicUrl' => route('tryouts.show', $tryout),
        ]);
    }

    public function update(UpdateTryoutRequest $request, Tryout $tryout): RedirectResponse
    {
        $data = $request->tryoutData();

        if ($request->hasFile('image')) {
            $data['image_path'] = $this->images->replace($request->file('image'), 'tryouts', $tryout->image_path);
        } elseif ($request->boolean('remove_image')) {
            $this->images->delete($tryout->image_path);
            $data['image_path'] = null;
        }

        $tryout->update($data);

        $this->syncSeoMeta->forModel($tryout, $request->validated('seo'), $request->file('seo_share_image'));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Tryout updated.')]);

        return to_route('admin.tryouts.edit', $tryout);
    }

    public function destroy(Tryout $tryout): RedirectResponse
    {
        $this->images->delete($tryout->image_path);
        $tryout->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Tryout deleted.')]);

        return to_route('admin.tryouts.index');
    }
}
