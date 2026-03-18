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
        Schema::create('project_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('key');
            // Claves de diseño global:
            //   color_primary, color_secondary, color_accent
            //   color_bg, color_text, color_header_bg, color_footer_bg
            // Claves por sección de página pública:
            //   section_hero_bg, section_hero_text
            //   section_catalog_bg, section_catalog_text
            //   section_footer_bg, section_footer_text
            // Claves de info del negocio:
            //   business_name, tagline, social_instagram, social_facebook
            //   social_tiktok, business_hours, custom_css
            $table->text('value')->nullable();
            $table->timestamps();
            $table->unique(['project_id', 'key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_settings');
    }
};
