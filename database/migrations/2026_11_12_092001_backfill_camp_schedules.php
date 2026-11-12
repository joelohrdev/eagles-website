<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Give every camp created before sessions existed one session and one day,
 * built from its old single price and date, and attach its registrations to
 * that day. Existing data keeps working under the session-based pricing.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $timezone = config('app.timezone');

        DB::table('camps')->orderBy('id')->each(function (object $camp) use ($timezone): void {
            if (DB::table('camp_sessions')->where('camp_id', $camp->id)->exists()) {
                return;
            }

            $now = now();
            $startsAt = Carbon::parse($camp->starts_at, $timezone);
            $endsAt = $camp->ends_at ? Carbon::parse($camp->ends_at, $timezone) : null;

            $sessionId = DB::table('camp_sessions')->insertGetId([
                'camp_id' => $camp->id,
                'name' => $camp->name,
                'price' => $camp->price,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $dayId = DB::table('camp_session_days')->insertGetId([
                'camp_session_id' => $sessionId,
                'date' => $startsAt->toDateString(),
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('camp_day_times')->insert([
                'camp_session_day_id' => $dayId,
                'starts_at' => $startsAt->format('H:i:s'),
                'ends_at' => $endsAt?->isSameDay($startsAt) ? $endsAt->format('H:i:s') : null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('camp_registrations')->where('camp_id', $camp->id)->orderBy('id')->each(function (object $registration) use ($dayId, $now): void {
                DB::table('camp_registration_days')->insert([
                    'camp_registration_id' => $registration->id,
                    'camp_session_day_id' => $dayId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            });
        });
    }

    /**
     * Reverse the migrations. The schedule tables' own migrations drop the
     * backfilled rows when they roll back, so there is nothing to undo here.
     */
    public function down(): void
    {
        //
    }
};
