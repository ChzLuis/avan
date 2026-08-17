<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // ARIN ya tiene estas columnas, anadidas alli por otra migracion con
        // distinto nombre (2026_07_29_000001). Sin guardas, correr migrate en
        // produccion tras desplegar este archivo reventaria con "duplicate
        // column". Idempotente = se puede correr en cualquier entorno.
        Schema::table('store_sections', function (Blueprint $table) {
            if (! Schema::hasColumn('store_sections', 'draft_show_desktop')) {
                $table->boolean('draft_show_desktop')->nullable()->after('draft_is_enabled');
            }
            if (! Schema::hasColumn('store_sections', 'draft_show_tablet')) {
                $table->boolean('draft_show_tablet')->nullable()->after('draft_show_desktop');
            }
            if (! Schema::hasColumn('store_sections', 'draft_show_mobile')) {
                $table->boolean('draft_show_mobile')->nullable()->after('draft_show_tablet');
            }
            if (! Schema::hasColumn('store_sections', 'draft_publish_from')) {
                $table->timestamp('draft_publish_from')->nullable()->after('publish_from');
            }
            if (! Schema::hasColumn('store_sections', 'draft_publish_until')) {
                $table->timestamp('draft_publish_until')->nullable()->after('draft_publish_from');
            }
        });

        DB::table('store_sections')
            ->where('has_draft', true)
            ->update([
                'draft_show_desktop' => DB::raw('show_desktop'),
                'draft_show_tablet' => DB::raw('show_tablet'),
                'draft_show_mobile' => DB::raw('show_mobile'),
                'draft_publish_from' => DB::raw('publish_from'),
                'draft_publish_until' => DB::raw('publish_until'),
            ]);
    }

    public function down(): void
    {
        Schema::table('store_sections', function (Blueprint $table) {
            $table->dropColumn([
                'draft_show_desktop',
                'draft_show_tablet',
                'draft_show_mobile',
                'draft_publish_from',
                'draft_publish_until',
            ]);
        });
    }
};
