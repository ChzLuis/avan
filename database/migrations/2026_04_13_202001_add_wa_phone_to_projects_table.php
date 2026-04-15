<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('projects', 'wa_phone')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->string('wa_phone', 30)->nullable()->after('slug');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('projects', 'wa_phone')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->dropColumn('wa_phone');
            });
        }
    }
};
