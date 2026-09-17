<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cotizacion B2B: cada linea recuerda QUE producto se pidio.
 *
 * Antes `quote_items` solo guardaba una descripcion de texto; al revisar la
 * solicitud nadie sabia que SKU ni de que marca era. Un distribuidor cotiza
 * por codigo, asi que la linea guarda producto, SKU, marca y unidad.
 *
 * Y las marcas ganan slug, descripcion e imagen: son lo que necesita una pagina
 * propia por marca (/marca/indeco) sin convertirlas en categorias.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quote_items', function (Blueprint $table) {
            $table->unsignedBigInteger('product_id')->nullable()->after('quote_id');
            $table->string('sku', 80)->nullable()->after('description');
            $table->string('brand', 100)->nullable()->after('sku');
            $table->string('unit', 30)->nullable()->after('brand');
            $table->index('product_id');
        });

        Schema::table('catalog_values', function (Blueprint $table) {
            $table->string('slug', 120)->nullable()->after('code');
            $table->text('description')->nullable()->after('slug');
            $table->string('image_url')->nullable()->after('description');
            $table->index('slug');
        });
    }

    public function down(): void
    {
        Schema::table('quote_items', function (Blueprint $table) {
            $table->dropIndex(['product_id']);
            $table->dropColumn(['product_id', 'sku', 'brand', 'unit']);
        });
        Schema::table('catalog_values', function (Blueprint $table) {
            $table->dropIndex(['slug']);
            $table->dropColumn(['slug', 'description', 'image_url']);
        });
    }
};
