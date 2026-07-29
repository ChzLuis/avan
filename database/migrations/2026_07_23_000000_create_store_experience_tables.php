<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('store_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('page', 40)->default('home');
            $table->string('component', 80);
            $table->string('variant', 60)->nullable();
            $table->json('content')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_enabled')->default(true);
            $table->boolean('show_desktop')->default(true);
            $table->boolean('show_tablet')->default(true);
            $table->boolean('show_mobile')->default(true);
            $table->timestamp('publish_from')->nullable();
            $table->timestamp('publish_until')->nullable();
            $table->timestamps();
            $table->index(['project_id', 'page', 'is_enabled', 'sort_order']);
        });

        Schema::create('store_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('key', 50);
            $table->string('title', 160);
            $table->json('content')->nullable();
            $table->boolean('is_enabled')->default(false);
            $table->timestamps();
            $table->unique(['project_id', 'key']);
        });

        Schema::create('store_popups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('title', 160)->nullable();
            $table->text('description')->nullable();
            $table->string('image_path')->nullable();
            $table->string('button_text', 80)->nullable();
            $table->string('button_url', 500)->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->unsignedSmallInteger('delay_seconds')->default(2);
            $table->enum('frequency', ['session', 'day', 'always'])->default('session');
            $table->boolean('show_desktop')->default(true);
            $table->boolean('show_mobile')->default(true);
            $table->boolean('is_enabled')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_popups');
        Schema::dropIfExists('store_pages');
        Schema::dropIfExists('store_sections');
    }
};
