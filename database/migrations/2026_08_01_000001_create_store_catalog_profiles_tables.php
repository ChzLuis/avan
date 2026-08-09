<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Perfiles de catálogo genéricos (Hombre/Mujer, Gamer/Oficina, Minorista/Mayorista…).
        // Sin valores fijos: cada tienda define sus propios perfiles.
        Schema::create('store_catalog_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('slug', 120);
            $table->string('menu_label', 120)->nullable();
            $table->string('description', 500)->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->boolean('is_default')->default(false);
            $table->boolean('show_in_menu')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            // Identidad visual opcional (todo nullable → hereda identidad global).
            $table->string('logo_path')->nullable();
            $table->string('mobile_logo_path')->nullable();
            $table->string('favicon_path')->nullable();
            $table->string('primary_color', 9)->nullable();
            $table->string('secondary_color', 9)->nullable();
            $table->string('header_bg_color', 9)->nullable();
            $table->string('header_text_color', 9)->nullable();
            $table->string('button_color', 9)->nullable();
            $table->string('footer_bg_color', 9)->nullable();
            $table->string('hero_desktop_path')->nullable();
            $table->string('hero_mobile_path')->nullable();
            $table->string('hero_title', 200)->nullable();
            $table->string('hero_description', 500)->nullable();
            $table->timestamps();

            // slug único por proyecto; el mismo slug se permite en otro proyecto.
            $table->unique(['project_id', 'slug']);
            $table->index(['project_id', 'is_enabled', 'sort_order'], 'scp_project_enabled_order');
        });

        // Pivote perfil ↔ categoría (sirve para categorías raíz y subcategorías;
        // la propia tabla categories distingue por parent_id).
        Schema::create('store_catalog_profile_category', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_catalog_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['store_catalog_profile_id', 'category_id'], 'scp_category_unique');
        });

        // Pivote perfil ↔ producto (un producto puede estar en varios perfiles).
        Schema::create('store_catalog_profile_product', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_catalog_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['store_catalog_profile_id', 'product_id'], 'scp_product_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_catalog_profile_product');
        Schema::dropIfExists('store_catalog_profile_category');
        Schema::dropIfExists('store_catalog_profiles');
    }
};
