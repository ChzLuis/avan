<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rifa_ventas', function (Blueprint $table) {
            $table->string('membership_number')->nullable()->after('ticket_code');
        });
    }

    public function down(): void
    {
        Schema::table('rifa_ventas', function (Blueprint $table) {
            $table->dropColumn('membership_number');
        });
    }
};
