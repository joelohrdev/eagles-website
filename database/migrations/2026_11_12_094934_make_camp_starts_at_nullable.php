<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Draft camps can be saved before their dates are known. starts_at is filled
 * from the schedule, and published camps always have one.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('camps', function (Blueprint $table) {
            $table->dateTime('starts_at')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations. Fails if a draft camp still has no start date.
     */
    public function down(): void
    {
        Schema::table('camps', function (Blueprint $table) {
            $table->dateTime('starts_at')->nullable(false)->change();
        });
    }
};
