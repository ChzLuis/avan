<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ciclo borrador → publicación para las páginas institucionales.
 *
 * `store_sections` tenía el ciclo completo (`draft_*`, `has_draft`,
 * `published_at`) y `store_pages` no: las secciones de Inicio se acumulaban en
 * borrador y las páginas —Nosotros, Contacto, Términos, Privacidad— se
 * publicaban en cuanto se guardaban. La etapa 09 prometía "publicar tienda"
 * cuando parte del contenido ya había salido sin pasar por ahí.
 *
 * Idempotente: comprueba cada columna antes de crearla.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_pages', function (Blueprint $table) {
            if (! Schema::hasColumn('store_pages', 'draft_title')) {
                $table->string('draft_title', 160)->nullable()->after('title');
            }
            if (! Schema::hasColumn('store_pages', 'draft_content')) {
                $table->json('draft_content')->nullable()->after('content');
            }
            if (! Schema::hasColumn('store_pages', 'draft_is_enabled')) {
                $table->boolean('draft_is_enabled')->nullable()->after('is_enabled');
            }
            if (! Schema::hasColumn('store_pages', 'has_draft')) {
                $table->boolean('has_draft')->default(false)->after('draft_is_enabled');
            }
            if (! Schema::hasColumn('store_pages', 'published_at')) {
                $table->timestamp('published_at')->nullable()->after('has_draft');
            }
        });

        // Lo ya guardado se considera publicado: nadie pierde contenido ni ve
        // su pagina como "pendiente de publicar" por esta migracion.
        \Illuminate\Support\Facades\DB::table('store_pages')
            ->whereNull('published_at')->update(['published_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('store_pages', function (Blueprint $table) {
            foreach (['draft_title', 'draft_content', 'draft_is_enabled', 'has_draft', 'published_at'] as $columna) {
                if (Schema::hasColumn('store_pages', $columna)) {
                    $table->dropColumn($columna);
                }
            }
        });
    }
};
