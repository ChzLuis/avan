<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('store_menus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100)->default('Menú principal');
            $table->string('location', 30)->default('primary');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['project_id', 'location']);
        });

        Schema::create('store_menu_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('store_menu_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('store_menu_items')->cascadeOnDelete();
            $table->string('label', 100);
            $table->string('destination_type', 30)->default('home');
            $table->unsignedBigInteger('destination_id')->nullable();
            $table->string('url', 1000)->nullable();
            $table->string('target', 10)->default('_self');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_enabled')->default(true);
            $table->boolean('show_desktop')->default(true);
            $table->boolean('show_tablet')->default(true);
            $table->boolean('show_mobile')->default(true);
            $table->timestamps();
            $table->index(['project_id', 'store_menu_id', 'parent_id', 'sort_order'], 'store_menu_items_navigation_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_menu_items');
        Schema::dropIfExists('store_menus');
    }
};
