<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            $table->boolean('combinable_with_multi_session')->default(false)->after('applies_to');
        });

        /** Coupons made before this setting existed already stacked with the multi-session price; keep them that way. */
        DB::table('coupons')->update(['combinable_with_multi_session' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            $table->dropColumn('combinable_with_multi_session');
        });
    }
};
