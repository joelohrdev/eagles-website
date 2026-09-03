<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RegistrationStatus;
use App\Http\Controllers\Controller;
use App\Models\Camp;
use App\Models\CampRegistration;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CampRegistrationController extends Controller
{
    public function index(Request $request, Camp $camp): Response
    {
        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', '');

        $registrations = $this->query($camp, $search, $status)
            ->paginate(25)
            ->withQueryString()
            ->through(fn (CampRegistration $registration) => [
                ...$registration->only(['id', 'player_first_name', 'player_last_name', 'player_birthdate', 'parent_name', 'email', 'phone', 'emergency_contact_name', 'emergency_contact_phone', 'medical_notes', 'registered_at', 'expires_at']),
                'player_name' => $registration->playerName(),
                'status' => $registration->status->value,
                'status_label' => $registration->status->label(),
                'order_number' => $registration->order?->number,
                'order_id' => $registration->order_id,
            ]);

        return Inertia::render('admin/camps/Registrations', [
            'camp' => [
                ...$camp->only(['id', 'name', 'slug', 'starts_at', 'capacity', 'price']),
                'spots_remaining' => $camp->spotsRemaining(),
                'paid_count' => $camp->registrations()->paid()->count(),
            ],
            'registrations' => $registrations,
            'filters' => ['q' => $search, 'status' => $status],
            'statuses' => collect(RegistrationStatus::cases())->map(fn (RegistrationStatus $s) => ['value' => $s->value, 'label' => $s->label()])->all(),
        ]);
    }

    public function export(Request $request, Camp $camp): StreamedResponse
    {
        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', '');
        $filename = 'camp-'.$camp->slug.'-registrations-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($camp, $search, $status): void {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                return;
            }

            fputcsv($handle, ['Player', 'Birthdate', 'Parent/Guardian', 'Email', 'Phone', 'Emergency contact', 'Emergency phone', 'Medical notes', 'Status', 'Order number', 'Registered at']);

            $this->query($camp, $search, $status)->lazy()->each(function (CampRegistration $registration) use ($handle): void {
                fputcsv($handle, [
                    $registration->playerName(),
                    $registration->player_birthdate->toDateString(),
                    $registration->parent_name,
                    $registration->email,
                    $registration->phone,
                    $registration->emergency_contact_name,
                    $registration->emergency_contact_phone,
                    $registration->medical_notes,
                    $registration->status->label(),
                    $registration->order?->number,
                    $registration->registered_at->format('Y-m-d H:i'),
                ]);
            });

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function destroy(Camp $camp, CampRegistration $registration): RedirectResponse
    {
        $registration->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Registration deleted.')]);

        return back();
    }

    /**
     * @return Builder<CampRegistration>
     */
    private function query(Camp $camp, string $search, string $status): Builder
    {
        return CampRegistration::query()
            ->whereBelongsTo($camp)
            ->with('order')
            ->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $q) => $q
                ->where('player_first_name', 'like', "%{$search}%")
                ->orWhere('player_last_name', 'like', "%{$search}%")
                ->orWhere('parent_name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
            ))
            ->when(RegistrationStatus::tryFrom($status), fn (Builder $query, RegistrationStatus $s) => $query->where('status', $s))
            ->latest('registered_at');
    }
}
