<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Http\Requests\Site\StoreContactRequest;
use App\Mail\ContactSubmissionReceived;
use App\Models\ContactSubmission;
use App\Services\SeoResolver;
use App\Services\SiteSettings;
use App\Support\Seo\Schema;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class ContactController extends Controller
{
    public function __construct(private SiteSettings $settings, private SeoResolver $seo) {}

    public function create(Request $request): Response
    {
        $org = $this->settings->only(['org_name', 'phone', 'email', 'address_line1', 'address_city', 'address_state', 'address_postal_code']);
        $intro = $this->settings->get('contact_intro');

        return Inertia::render('site/Contact/Index', [
            'intro' => $intro,
            'org' => $org,
            'sent' => $request->boolean('sent'),
            'seo' => $this->seo->forRoute('contact', [
                'title' => 'Contact Us',
                'description' => $intro,
                'json_ld' => [
                    Schema::breadcrumbs([
                        ['name' => 'Home', 'url' => route('home')],
                        ['name' => 'Contact', 'url' => route('contact')],
                    ]),
                ],
            ])->toArray(),
        ]);
    }

    public function store(StoreContactRequest $request): RedirectResponse
    {
        $submission = ContactSubmission::create($request->safe()->except('website'));

        if ($orgEmail = $this->settings->get('email')) {
            Mail::to($orgEmail)->queue(new ContactSubmissionReceived($submission));
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Thanks! Your message has been sent.')]);

        return to_route('contact', ['sent' => 1]);
    }
}
