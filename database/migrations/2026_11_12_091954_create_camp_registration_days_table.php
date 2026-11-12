<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * MySQL caps identifiers at 64 characters, and Laravel's default name for
     * this index is 68, so it gets a short name.
     */
    private const string UNIQUE_INDEX = 'camp_registration_days_unique';

    /**
     * Run the migrations.
     *
     * The first production deploy created this table but failed on the index
     * (MySQL can't roll back DDL), leaving the table and its foreign keys
     * without the migration being recorded. When the table already exists,
     * only the missing index is added.
     */
    public function up(): void
    {
        if (Schema::hasTable('camp_registration_days')) {
            if (! Schema::hasIndex('camp_registration_days', self::UNIQUE_INDEX)) {
                Schema::table('camp_registration_days', function (Blueprint $table) {
                    $table->unique(['camp_registration_id', 'camp_session_day_id'], self::UNIQUE_INDEX);
                });
            }

            return;
        }

        Schema::create('camp_registration_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('camp_registration_id')->constrained()->cascadeOnDelete();
            $table->foreignId('camp_session_day_id')->constrained()->restrictOnDelete();
            $table->timestamps();

            $table->unique(['camp_registration_id', 'camp_session_day_id'], self::UNIQUE_INDEX);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('camp_registration_days');
    }
};
