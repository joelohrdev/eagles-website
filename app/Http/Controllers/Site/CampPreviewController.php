<?php

namespace App\Http\Controllers\Site;

use App\Actions\Camps\QuoteCampSelection;
use App\Http\Controllers\Controller;
use App\Http\Requests\Site\QuoteCampRegistrationRequest;
use App\Models\Camp;
use App\Services\CampPreviewAccess;
use App\Services\SeoResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The camp page and registration form as families will see them, for drafts
 * too. Signed-in staff go straight in; anyone else needs the camp's preview
 * passcode, or is sent to log in when the camp has none. Nothing here can be
 * submitted or saved, and every page is marked noindex.
 */
class CampPreviewController extends Controller
{
    public function __construct(
        private CampPreviewAccess $access,
        private SeoResolver $seo,
    ) {}

    public function show(Request $request, Camp $camp): Response|RedirectResponse
    {
        return $this->guard($request, $camp, 'camps.preview') ?? Inertia::render('site/Camps/Show', [
            ...$this->previewProps($request, $camp, 'Preview: '),
            'registered' => false,
        ]);
    }

    /**
     * The registration form with live pricing (previewQuote); it can't be submitted.
     */
    public function register(Request $request, Camp $camp): Response|RedirectResponse
    {
        return $this->guard($request, $camp, 'camps.preview.register') ?? Inertia::render('site/Camps/Register', [
            ...$this->previewProps($request, $camp, 'Preview registration: '),
            'quoteUrl' => route('camps.preview.quote', $camp),
        ]);
    }

    /**
     * Price a selection on the preview form. Same rules as the public quote, never saves anything.
     */
    public function quote(Request $request, Camp $camp, QuoteCampSelection $quoteCampSelection): JsonResponse
    {
        abort_unless($this->access->allows($request, $camp), 403);

        $validated = $request->validate(
            QuoteCampRegistrationRequest::selectionRules(),
            attributes: QuoteCampRegistrationRequest::selectionAttributes(),
        );

        return response()->json($quoteCampSelection->handle(
            $camp,
            array_values(array_map('intval', $validated['days'])),
            $validated['camp_age_group_id'] ?? null,
            $validated['coupon_code'] ?? null,
        )->toArray());
    }

    /**
     * Check the passcode and open the preview page the visitor was heading to.
     */
    public function unlock(Request $request, Camp $camp): RedirectResponse
    {
        abort_unless($this->access->hasPasscode($camp), 404);

        $validated = $request->validate([
            'passcode' => ['required', 'digits:4'],
            'to' => ['nullable', 'in:camps.preview,camps.preview.register'],
        ], ['passcode.digits' => 'Enter the 4-digit passcode.']);

        if (! $this->access->unlock($request->session(), $camp, $validated['passcode'])) {
            return back()->withErrors(['passcode' => __('That passcode isn’t right.')]);
        }

        return to_route($validated['to'] ?? 'camps.preview', $camp);
    }

    /**
     * Null when the visitor may see the preview; otherwise the passcode screen,
     * or a trip to the login page (which returns here) when there's no passcode
     * or they chose to log in.
     */
    private function guard(Request $request, Camp $camp, string $routeName): Response|RedirectResponse|null
    {
        if ($this->access->allows($request, $camp)) {
            return null;
        }

        if (! $this->access->hasPasscode($camp) || $request->boolean('login')) {
            return redirect()->guest(route('login'));
        }

        return Inertia::render('site/Camps/PreviewLocked', [
            'camp' => ['name' => $camp->name, 'slug' => $camp->slug],
            'unlockUrl' => route('camps.preview.unlock', $camp),
            'to' => $routeName,
            'loginUrl' => route($routeName, ['camp' => $camp, 'login' => 1]),
            'seo' => $this->seo->forRoute('camps.preview', [
                'title' => "Preview: {$camp->name}",
                'robots' => 'noindex,nofollow',
            ])->toArray(),
        ]);
    }

    /**
     * Props shared by the preview pages: the camp presented as if live (so the
     * registration state matches what families will see; never saved), the
     * preview banner's links, and noindex SEO.
     *
     * @return array<string, mixed>
     */
    private function previewProps(Request $request, Camp $camp, string $titlePrefix): array
    {
        $camp->load('seoMeta');
        $isDraft = ! $camp->is_published;
        $camp->is_published = true;

        return [
            'camp' => CampController::presentDetail($camp),
            'preview' => [
                'is_draft' => $isDraft,
                'edit_url' => $request->user() ? route('admin.camps.edit', $camp) : null,
                'camp_url' => route('camps.preview', $camp),
                'register_url' => route('camps.preview.register', $camp),
            ],
            'seo' => $this->seo->forModel($camp, [
                'title' => $titlePrefix.$camp->name,
                'description' => CampController::autoDescription($camp),
                'robots' => 'noindex,nofollow',
            ])->toArray(),
        ];
    }
}
