<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('categories', 'color')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->string('color', 20)->nullable()->after('image_url');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('categories', 'color')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->dropColumn('color');
            });
        }
    }
};
