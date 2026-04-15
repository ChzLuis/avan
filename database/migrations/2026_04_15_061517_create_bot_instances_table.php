<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('bot_instances')) {
            Schema::create('bot_instances', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('project_id');
                $table->string('name', 80);               // "Bot Principal"
                $table->string('bot_type', 40)->unique(); // "main", "rifa", "soporte"
                $table->string('description', 150)->nullable();
                $table->string('icon_color', 20)->default('green'); // green, purple, blue, orange
                $table->unsignedSmallInteger('port')->default(3001);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->foreign('project_id')->references('id')->on('projects')->cascadeOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bot_instances');
    }
};
