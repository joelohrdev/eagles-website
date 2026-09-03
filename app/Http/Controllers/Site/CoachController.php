<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Coach;
use App\Services\SeoResolver;
use App\Support\Seo\Schema;
use Inertia\Inertia;
use Inertia\Response;

class CoachController extends Controller
{
    public function __construct(private SeoResolver $seo) {}

    public function index(): Response
    {
        $coaches = Coach::query()->active()->ordered()->get();

        return Inertia::render('site/Coaches/Index', [
            'coaches' => $coaches,
            'seo' => $this->seo->forRoute('coaches.index', [
                'title' => 'Coaching Staff',
                'description' => 'Meet the Eagles Baseball Travel coaching staff — experienced coaches dedicated to developing young players on and off the field.',
                'json_ld' => [
                    Schema::breadcrumbs([
                        ['name' => 'Home', 'url' => route('home')],
                        ['name' => 'Coaching Staff', 'url' => route('coaches.index')],
                    ]),
                    ...$coaches->map(fn (Coach $coach) => Schema::person($coach))->all(),
                ],
            ])->toArray(),
        ]);
    }
}
