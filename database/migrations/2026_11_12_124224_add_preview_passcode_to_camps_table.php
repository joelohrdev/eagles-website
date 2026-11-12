<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('camps', function (Blueprint $table) {
            /** Encrypted 4-digit code that unlocks the camp's preview without logging in. */
            $table->text('preview_passcode')->nullable()->after('is_published');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('camps', function (Blueprint $table) {
            $table->dropColumn('preview_passcode');
        });
    }
};
