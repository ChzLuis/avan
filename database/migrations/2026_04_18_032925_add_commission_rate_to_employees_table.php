<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->decimal('commission_rate', 5, 2)->default(0)->after('is_active');
            $table->time('work_start')->nullable()->after('commission_rate');
            $table->time('work_end')->nullable()->after('work_start');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['commission_rate', 'work_start', 'work_end']);
        });
    }
};
