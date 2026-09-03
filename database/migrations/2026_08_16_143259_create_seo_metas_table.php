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
        Schema::create('seo_metas', function (Blueprint $table) {
            $table->id();
            $table->string('route_key')->nullable()->unique();
            $table->nullableMorphs('metable');
            $table->string('title')->nullable();
            $table->string('description', 500)->nullable();
            $table->string('canonical_url')->nullable();
            $table->string('robots')->nullable();
            $table->string('share_title')->nullable();
            $table->string('share_description', 500)->nullable();
            $table->string('share_image_path')->nullable();
            $table->string('share_image_alt')->nullable();
            $table->string('twitter_card')->nullable();
            $table->json('json_ld')->nullable();
            $table->timestamps();

            $table->unique(['metable_type', 'metable_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seo_metas');
    }
};
