<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            if (!Schema::hasColumn('services', 'modality')) $table->string('modality', 30)->nullable()->after('duration_min');
            if (!Schema::hasColumn('services', 'notes')) $table->text('notes')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $cols = array_filter(['modality','notes'], fn($c) => Schema::hasColumn('services', $c));
            if ($cols) $table->dropColumn(array_values($cols));
        });
    }
};
