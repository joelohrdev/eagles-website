<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Services\SeoResolver;
use App\Support\Seo\Schema;
use Inertia\Inertia;
use Inertia\Response;

class TeamController extends Controller
{
    public function __construct(private SeoResolver $seo) {}

    public function index(): Response
    {
        $teams = Team::query()->active()->with('coach:id,name,title')->ordered()->get();

        return Inertia::render('site/Teams/Index', [
            'teams' => $teams,
            'seo' => $this->seo->forRoute('teams.index', [
                'title' => 'Teams',
                'description' => 'Eagles Baseball Travel fields competitive youth travel baseball teams from 9U through 17U, each led by experienced coaches focused on player development.',
                'json_ld' => [
                    Schema::breadcrumbs([
                        ['name' => 'Home', 'url' => route('home')],
                        ['name' => 'Teams', 'url' => route('teams.index')],
                    ]),
                    ...$teams->map(fn (Team $team) => Schema::sportsTeam($team))->all(),
                ],
            ])->toArray(),
        ]);
    }
}
