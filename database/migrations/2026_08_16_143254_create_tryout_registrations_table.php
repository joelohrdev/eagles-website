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
        Schema::create('tryout_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tryout_id')->constrained()->cascadeOnDelete();
            $table->string('player_first_name');
            $table->string('player_last_name');
            $table->date('player_birthdate');
            $table->string('parent_name');
            $table->string('email')->index();
            $table->string('phone');
            $table->string('current_team')->nullable();
            $table->string('primary_position')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('registered_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tryout_registrations');
    }
};
