<?php

namespace App\Http\Controllers\Site;

use App\Actions\Camps\RegisterForCamp;
use App\Exceptions\CampRegistrationClosed;
use App\Http\Controllers\Controller;
use App\Http\Requests\Site\StoreCampRegistrationRequest;
use App\Models\Camp;
use App\Services\SeoResolver;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class CampRegistrationController extends Controller
{
    public function __construct(
        private SeoResolver $seo,
        private RegisterForCamp $registerForCamp,
    ) {}

    public function create(Camp $camp): Response
    {
        abort_unless($camp->is_published, 404);

        $seo = $this->seo->forRoute('camps.register', [
            'title' => "Register — {$camp->name}",
            'description' => CampController::autoDescription($camp),
            'share_image_path' => $camp->image_path,
            'robots' => 'noindex,follow',
        ]);

        return Inertia::render('site/Camps/Register', [
            'camp' => CampController::presentDetail($camp),
            'seo' => $seo->toArray(),
        ]);
    }

    public function store(StoreCampRegistrationRequest $request, Camp $camp): RedirectResponse|SymfonyResponse
    {
        abort_unless($camp->is_published, 404);

        if ($request->isSpam()) {
            return to_route('camps.show', $camp);
        }

        try {
            $result = $this->registerForCamp->handle($camp, $request->safe()->except(['website']));
        } catch (CampRegistrationClosed $e) {
            return back()->withErrors(['registration' => $e->getMessage()])->withInput();
        }

        if ($result['checkout'] !== null) {
            return Inertia::location($result['checkout']->url);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __("You're registered! Check your email for confirmation.")]);

        return to_route('camps.show', ['camp' => $camp, 'registered' => 1]);
    }
}
