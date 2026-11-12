<?php

namespace App\Console\Commands;

use App\Actions\Orders\CancelPendingOrder;
use App\Enums\RegistrationStatus;
use App\Models\CampRegistration;
use App\Services\ActivityLogger;
use Illuminate\Console\Command;

class ExpirePendingCampRegistrations extends Command
{
    protected $signature = 'camps:expire-pending';

    protected $description = 'Cancel unpaid camp registrations whose payment hold has expired and release their spots';

    public function handle(CancelPendingOrder $cancelPendingOrder, ActivityLogger $activity): int
    {
        $activity->actingAs(ActivityLogger::SYSTEM);

        $expired = CampRegistration::query()
            ->with('order')
            ->where('status', RegistrationStatus::PendingPayment)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->get();

        foreach ($expired as $registration) {
            if ($registration->order) {
                $cancelPendingOrder->handle($registration->order, 'Payment hold expired');
            }

            $registration->forceFill(['status' => RegistrationStatus::Cancelled])->save();

            $activity->log('camp_registration.expired', "{$registration->playerName()}'s unpaid hold expired", $registration, [
                'player' => $registration->playerName(),
                'email' => $registration->email,
            ]);
        }

        $this->info("Expired {$expired->count()} pending camp registration(s).");

        return self::SUCCESS;
    }
}
