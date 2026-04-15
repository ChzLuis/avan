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
        if (!Schema::hasTable('bot_sessions')) {
            Schema::create('bot_sessions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('flow_id');
                $table->string('wa_number', 30);
                $table->string('current_state', 60)->default('inicio');
                $table->json('data')->nullable();
                $table->timestamp('last_activity_at')->nullable();
                $table->timestamps();

                $table->foreign('flow_id')->references('id')->on('bot_flows')->cascadeOnDelete();
                $table->unique(['flow_id', 'wa_number']);
                $table->index('wa_number');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bot_sessions');
    }
};
