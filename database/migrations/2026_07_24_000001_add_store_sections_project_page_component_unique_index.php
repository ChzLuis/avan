<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $duplicates = DB::table('store_sections')
            ->select('project_id', 'page', 'component', DB::raw('GROUP_CONCAT(id) AS ids'), DB::raw('COUNT(*) AS count'))
            ->groupBy('project_id', 'page', 'component')
            ->having('count', '>', 1)
            ->get();

        if ($duplicates->isNotEmpty()) {
            $details = $duplicates->map(fn ($row) => sprintf(
                'project_id=%s page=%s component=%s ids=%s',
                $row->project_id,
                $row->page,
                $row->component,
                $row->ids
            ))->implode("\n");

            throw new RuntimeException("No se puede crear el índice único store_sections_project_page_component_unique, se encontraron duplicados:\n{$details}");
        }

        Schema::table('store_sections', function (Blueprint $table) {
            $table->unique(['project_id', 'page', 'component'], 'store_sections_project_page_component_unique');
        });
    }

    public function down(): void
    {
        Schema::table('store_sections', function (Blueprint $table) {
            $table->dropUnique('store_sections_project_page_component_unique');
        });
    }
};
