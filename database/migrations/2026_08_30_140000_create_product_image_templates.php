<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Plantillas automaticas de imagen de producto.
 *
 * Aisladas por proyecto: una tienda no puede ver ni aplicar la plantilla, el
 * logo ni el fondo de otra. Cada negocio puede guardar varias configuraciones
 * y tener UNA activa.
 *
 * La imagen compuesta NO sustituye a la original: `product_images.url` sigue
 * siendo la foto de siempre y la version generada vive en `generated_url`.
 * Asi apagar la plantilla devuelve el catalogo a su estado anterior sin
 * borrar un solo archivo, y regenerar parte siempre del original.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('product_image_templates')) {
            Schema::create('product_image_templates', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_id')->constrained()->cascadeOnDelete();
                $table->string('name', 80)->default('Plantilla');
                $table->boolean('is_active')->default(false);
                $table->boolean('enabled')->default(false);
                // Configuracion completa del compositor. JSON para poder anadir
                // controles sin una migracion por cada uno.
                $table->json('config');
                // Huella de la configuracion: si no cambia, no se regenera nada.
                $table->string('hash', 32)->nullable();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->index(['project_id', 'is_active']);
            });
        }

        Schema::table('product_images', function (Blueprint $table) {
            if (! Schema::hasColumn('product_images', 'generated_url')) {
                // Version compuesta. NULL = todavia no generada -> se usa la original.
                $table->string('generated_url', 255)->nullable()->after('url');
            }
            if (! Schema::hasColumn('product_images', 'generated_hash')) {
                // Con que configuracion se genero: distinta huella = obsoleta.
                $table->string('generated_hash', 32)->nullable()->after('generated_url');
            }
            if (! Schema::hasColumn('product_images', 'generated_at')) {
                $table->timestamp('generated_at')->nullable()->after('generated_hash');
            }
            if (! Schema::hasColumn('product_images', 'generation_status')) {
                // pendiente | procesando | completado | error
                $table->string('generation_status', 20)->nullable()->after('generated_at');
            }
            if (! Schema::hasColumn('product_images', 'generation_error')) {
                $table->string('generation_error', 255)->nullable()->after('generation_status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('product_images', function (Blueprint $table) {
            foreach (['generated_url', 'generated_hash', 'generated_at', 'generation_status', 'generation_error'] as $col) {
                if (Schema::hasColumn('product_images', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
        Schema::dropIfExists('product_image_templates');
    }
};
