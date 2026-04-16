<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bot_states', function (Blueprint $table) {
            $table->string('validation_pattern', 200)->nullable()->after('input_type');
            $table->integer('validation_min')->nullable()->after('validation_pattern');
            $table->integer('validation_max')->nullable()->after('validation_min');
            $table->string('validation_error', 200)->nullable()->after('validation_max');
        });
    }

    public function down(): void
    {
        Schema::table('bot_states', function (Blueprint $table) {
            $table->dropColumn(['validation_pattern', 'validation_min', 'validation_max', 'validation_error']);
        });
    }
};
