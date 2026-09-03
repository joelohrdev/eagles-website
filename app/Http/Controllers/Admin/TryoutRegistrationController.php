<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tryout;
use App\Models\TryoutRegistration;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TryoutRegistrationController extends Controller
{
    public function index(Request $request, Tryout $tryout): Response
    {
        $search = trim((string) $request->query('q', ''));

        $registrations = $tryout->registrations()
            ->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $q) => $q
                ->where('player_first_name', 'like', "%{$search}%")
                ->orWhere('player_last_name', 'like', "%{$search}%")
                ->orWhere('parent_name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
            ))
            ->latest('registered_at')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('admin/tryouts/Registrations', [
            'tryout' => [
                ...$tryout->toArray(),
                'registration_state' => $tryout->registrationState(),
                'spots_remaining' => $tryout->spotsRemaining(),
                'registrations_count' => $tryout->registrations()->count(),
            ],
            'registrations' => $registrations,
            'filters' => ['q' => $search],
        ]);
    }

    public function export(Tryout $tryout): StreamedResponse
    {
        $filename = Str::slug($tryout->title).'-registrations.csv';

        return response()->streamDownload(function () use ($tryout): void {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['Player', 'Birthdate', 'Parent/Guardian', 'Email', 'Phone', 'Current team', 'Position', 'Notes', 'Registered at']);

            $tryout->registrations()
                ->orderBy('registered_at')
                ->lazy()
                ->each(function (TryoutRegistration $registration) use ($handle): void {
                    fputcsv($handle, [
                        $registration->playerName(),
                        $registration->player_birthdate->toDateString(),
                        $registration->parent_name,
                        $registration->email,
                        $registration->phone,
                        $registration->current_team,
                        $registration->primary_position,
                        $registration->notes,
                        $registration->registered_at->toDateTimeString(),
                    ]);
                });

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function destroy(Tryout $tryout, TryoutRegistration $registration): RedirectResponse
    {
        $registration->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Registration removed.')]);

        return to_route('admin.tryouts.registrations.index', $tryout);
    }
}
