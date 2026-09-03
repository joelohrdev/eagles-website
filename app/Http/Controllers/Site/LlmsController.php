<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Camp;
use App\Models\Team;
use App\Models\Tryout;
use App\Services\PageVisibility;
use App\Services\SiteSettings;
use App\Support\Seo\StaticPages;
use Illuminate\Http\Response;

class LlmsController extends Controller
{
    public function __construct(private SiteSettings $settings, private PageVisibility $pages) {}

    public function __invoke(): Response
    {
        $s = $this->settings->all();

        $lines = ["# {$s['org_name']}", '', '> '.$s['home_intro'], ''];

        $lines[] = '## Contact';
        $lines[] = '- Website: '.route('home').'';
        if ($s['phone']) {
            $lines[] = "- Phone: {$s['phone']}";
        }
        if ($s['email']) {
            $lines[] = "- Email: {$s['email']}";
        }
        $address = collect([$s['address_line1'], $s['address_city'], $s['address_state'], $s['address_postal_code']])->filter()->implode(', ');
        if ($address) {
            $lines[] = "- Address: {$address}";
        }
        if ($s['service_area']) {
            $lines[] = "- Service area: {$s['service_area']}";
        }
        $lines[] = '';

        $lines[] = '## Pages';
        foreach (StaticPages::all() as $page) {
            if (! $this->pages->allowsRoute($page['route'])) {
                continue;
            }

            $lines[] = "- [{$page['label']}](".route($page['route'])."): {$page['description']}";
        }
        $lines[] = '';

        $divisions = Team::query()->active()->ordered()->get(['division'])->pluck('division')->unique()->values();
        if ($divisions->isNotEmpty()) {
            $lines[] = '## Teams';
            $lines[] = 'Divisions: '.$divisions->implode(', ');
            $lines[] = '';
        }

        $tryouts = Tryout::query()->published()->upcoming()->ordered()->take(10)->get();
        if ($tryouts->isNotEmpty()) {
            $lines[] = '## Upcoming tryouts';
            foreach ($tryouts as $tryout) {
                $lines[] = "- [{$tryout->title}](".route('tryouts.show', $tryout)."): {$tryout->event_at->format('F j, Y g:i A')}"
                    .($tryout->location ? " at {$tryout->location}" : '')
                    .' — registration '.$tryout->registrationState();
            }
            $lines[] = '';
        }

        $camps = $this->pages->isEnabled('camps')
            ? Camp::query()->published()->upcoming()->ordered()->take(10)->get()
            : collect();
        if ($camps->isNotEmpty()) {
            $lines[] = '## Upcoming camps';
            foreach ($camps as $camp) {
                $price = $camp->isFree() ? 'Free' : '$'.number_format($camp->price / 100, 2);
                $lines[] = "- [{$camp->name}](".route('camps.show', $camp)."): {$camp->starts_at->format('F j, Y')}"
                    .($camp->location ? " at {$camp->location}" : '')
                    .($camp->age_range ? " ({$camp->age_range})" : '')
                    ." — {$price}";
            }
            $lines[] = '';
        }

        return response(implode("\n", $lines), 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
