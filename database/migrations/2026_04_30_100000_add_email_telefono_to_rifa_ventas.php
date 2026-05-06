<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rifa_ventas', function (Blueprint $table) {
            $table->string('email')->nullable()->after('ciudad');
            $table->string('telefono')->nullable()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('rifa_ventas', function (Blueprint $table) {
            $table->dropColumn(['email', 'telefono']);
        });
    }
};
