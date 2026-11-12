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
        Schema::create('camp_registration_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('camp_registration_id')->constrained()->cascadeOnDelete();
            $table->foreignId('camp_session_day_id')->constrained()->restrictOnDelete();
            $table->timestamps();

            $table->unique(['camp_registration_id', 'camp_session_day_id']);
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
