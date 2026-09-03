<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Http\Requests\Site\StoreTryoutRegistrationRequest;
use App\Mail\TryoutRegistrationConfirmation;
use App\Mail\TryoutRegistrationReceived;
use App\Models\Tryout;
use App\Models\TryoutRegistration;
use App\Services\SeoResolver;
use App\Services\SiteSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class TryoutRegistrationController extends Controller
{
    public function __construct(
        private SeoResolver $seo,
        private SiteSettings $settings,
    ) {}

    public function create(Tryout $tryout): Response
    {
        abort_unless($tryout->is_published, 404);

        return Inertia::render('site/Tryouts/Register', [
            'tryout' => TryoutController::present($tryout),
            'positions' => StoreTryoutRegistrationRequest::POSITIONS,
            'seo' => $this->seo->forRoute('tryouts.register', [
                'title' => "Register — {$tryout->title}",
                'description' => TryoutController::autoDescription($tryout),
                'share_image_path' => $tryout->image_path,
                'robots' => 'noindex,follow',
                'canonical_url' => route('tryouts.show', $tryout),
            ])->toArray(),
        ]);
    }

    public function store(StoreTryoutRegistrationRequest $request, Tryout $tryout): RedirectResponse
    {
        abort_unless($tryout->is_published, 404);

        if ($request->isSpam()) {
            return back();
        }

        $registration = DB::transaction(function () use ($request, $tryout): ?TryoutRegistration {
            $locked = Tryout::query()->whereKey($tryout->id)->lockForUpdate()->firstOrFail();

            if (! $locked->isRegistrationOpen()) {
                return null;
            }

            return $locked->registrations()->create([
                ...$request->safe()->except('website'),
                'registered_at' => now(),
            ]);
        });

        if ($registration === null) {
            return back()->withErrors(['registration' => __('Registration for this tryout is not open.')]);
        }

        Mail::to($registration->email)->queue(new TryoutRegistrationConfirmation($registration));

        if ($orgEmail = $this->settings->get('email')) {
            Mail::to($orgEmail)->queue(new TryoutRegistrationReceived($registration));
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __("You're registered! Check your email for confirmation.")]);

        return redirect()->to(route('tryouts.show', $tryout).'?registered=1');
    }
}
