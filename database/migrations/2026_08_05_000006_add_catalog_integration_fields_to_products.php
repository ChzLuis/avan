<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Columnas nullable para distinguir producto manual de producto sincronizado
 * desde un ERP externo. Nada existente cambia: un producto creado a mano
 * sigue con estos 3 campos en null, exactamente como hoy.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'catalog_integration_id')) {
                $table->foreignId('catalog_integration_id')->nullable()->after('project_id')
                    ->constrained('catalog_integrations')->nullOnDelete();
            }
            if (!Schema::hasColumn('products', 'external_sync_status')) {
                $table->string('external_sync_status', 20)->nullable()->after('catalog_integration_id'); // synced|conflict|orphaned
            }
            if (!Schema::hasColumn('products', 'owner_scope')) {
                $table->json('owner_scope')->nullable()->after('external_sync_status'); // qué campos controla el ERP en este producto
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            foreach (['owner_scope', 'external_sync_status'] as $c) {
                if (Schema::hasColumn('products', $c)) $table->dropColumn($c);
            }
            if (Schema::hasColumn('products', 'catalog_integration_id')) {
                $table->dropConstrainedForeignId('catalog_integration_id');
            }
        });
    }
};
