<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Color de la barra superior por perfil de catálogo.
 *
 * La barra de avisos ("Envíos a todo el Perú…") se pintaba con un color fijo de
 * la tienda, así que al cambiar de mundo —Niño / Niña— todo cambiaba de color
 * menos ella. Los perfiles tenían color de primario, cabecera, botón y pie, pero
 * ninguno para esa barra: no había dónde guardarlo.
 *
 * Nullable a propósito: el perfil que no lo defina sigue heredando el color de
 * la tienda, así que ninguna tienda existente cambia de aspecto.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_catalog_profiles', function (Blueprint $table) {
            if (!Schema::hasColumn('store_catalog_profiles', 'announcement_bg_color')) {
                $table->string('announcement_bg_color', 9)->nullable()->after('footer_bg_color');
            }
        });
    }

    public function down(): void
    {
        Schema::table('store_catalog_profiles', function (Blueprint $table) {
            if (Schema::hasColumn('store_catalog_profiles', 'announcement_bg_color')) {
                $table->dropColumn('announcement_bg_color');
            }
        });
    }
};
