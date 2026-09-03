<?php

namespace App\Console\Commands;

use App\Actions\Orders\CancelPendingOrder;
use App\Enums\RegistrationStatus;
use App\Models\CampRegistration;
use Illuminate\Console\Command;

class ExpirePendingCampRegistrations extends Command
{
    protected $signature = 'camps:expire-pending';

    protected $description = 'Cancel unpaid camp registrations whose payment hold has expired and release their spots';

    public function handle(CancelPendingOrder $cancelPendingOrder): int
    {
        $expired = CampRegistration::query()
            ->with('order')
            ->where('status', RegistrationStatus::PendingPayment)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->get();

        foreach ($expired as $registration) {
            if ($registration->order) {
                $cancelPendingOrder->handle($registration->order);
            }

            $registration->forceFill(['status' => RegistrationStatus::Cancelled])->save();
        }

        $this->info("Expired {$expired->count()} pending camp registration(s).");

        return self::SUCCESS;
    }
}
