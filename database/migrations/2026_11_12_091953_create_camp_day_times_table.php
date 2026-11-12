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
        Schema::create('camp_day_times', function (Blueprint $table) {
            $table->id();
            $table->foreignId('camp_session_day_id')->constrained()->cascadeOnDelete();
            $table->foreignId('camp_age_group_id')->nullable()->constrained()->cascadeOnDelete();
            $table->time('starts_at')->nullable();
            $table->time('ends_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('camp_day_times');
    }
};
