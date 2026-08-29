<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('product_attributes')) {
            Schema::create('product_attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('name', 80);
            $table->string('slug', 90);
            $table->string('type', 20)->default('select');
            $table->boolean('is_variant')->default(true);
            $table->boolean('is_filterable')->default(true);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['project_id', 'slug']);
            $table->index(['project_id', 'is_active', 'sort_order']);
            });
        }

        if (! Schema::hasTable('product_attribute_values')) {
            Schema::create('product_attribute_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_attribute_id')->constrained()->cascadeOnDelete();
            $table->string('label', 100);
            $table->string('value', 110);
            $table->string('color_hex', 7)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['product_attribute_id', 'value']);
            // Nombre explícito: el autogenerado supera los 64 caracteres de
            // MySQL y aborta la migración en una BD limpia (tumbó ARIN).
            $table->index(['project_id', 'product_attribute_id', 'is_active'], 'pav_project_attr_active_idx');
            });
        }

        if (! Schema::hasTable('product_variants')) {
            Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_image_id')->nullable()->constrained('product_images')->nullOnDelete();
            $table->string('signature', 190);
            $table->string('sku', 100)->nullable();
            $table->string('barcode', 100)->nullable();
            $table->decimal('price', 12, 2)->nullable();
            $table->decimal('compare_price', 12, 2)->nullable();
            $table->decimal('wholesale_price', 12, 2)->nullable();
            $table->integer('stock')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['product_id', 'signature']);
            $table->index(['project_id', 'product_id', 'is_active']);
            $table->unique(['project_id', 'sku']);
            });
        }

        if (! Schema::hasTable('product_variant_values')) {
            Schema::create('product_variant_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_attribute_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_attribute_value_id')
                ->constrained('product_attribute_values', indexName: 'papv_attribute_value_fk')
                ->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['product_variant_id', 'product_attribute_id'], 'product_variant_attribute_unique');
            $table->unique(['product_variant_id', 'product_attribute_value_id'], 'product_variant_value_unique');
            $table->index(['project_id', 'product_attribute_id', 'product_attribute_value_id'], 'product_variant_filter_index');
            });
        }

        if (! Schema::hasTable('product_attribute_product_value')) {
            Schema::create('product_attribute_product_value', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_attribute_id')->constrained()->cascadeOnDelete();
            // Nombre explícito: el autogenerado (66 chars) supera el límite de
            // 64 de MySQL y abortaba la migración (tumbó ARIN). Distinto del
            // 'papv_attribute_value_fk' de product_variant_values: los nombres
            // de FK son únicos por base de datos.
            $table->foreignId('product_attribute_value_id')
                ->constrained('product_attribute_values', indexName: 'papv_pivot_value_fk')
                ->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['product_id', 'product_attribute_value_id'], 'product_attribute_product_value_unique');
            $table->index(['project_id', 'product_attribute_id', 'product_attribute_value_id'], 'product_attribute_filter_index');
            });
        }

        // MySQL can leave this table partially created when a generated
        // constraint name exceeds its 64-character identifier limit. Repair
        // that safe, additive state instead of dropping existing rows.
        $pivotForeignColumns = collect(Schema::getForeignKeys('product_attribute_product_value'))
            ->flatMap(fn (array $foreignKey) => $foreignKey['columns'] ?? [])
            ->all();

        if (! in_array('product_attribute_value_id', $pivotForeignColumns, true)) {
            Schema::table('product_attribute_product_value', function (Blueprint $table) {
                $table->foreign('product_attribute_value_id', 'papv_pivot_value_fk')
                    ->references('id')
                    ->on('product_attribute_values')
                    ->cascadeOnDelete();
            });
        }

        if (! Schema::hasIndex('product_attribute_product_value', 'product_attribute_product_value_unique')) {
            Schema::table('product_attribute_product_value', function (Blueprint $table) {
                $table->unique(['product_id', 'product_attribute_value_id'], 'product_attribute_product_value_unique');
            });
        }

        if (! Schema::hasIndex('product_attribute_product_value', 'product_attribute_filter_index')) {
            Schema::table('product_attribute_product_value', function (Blueprint $table) {
                $table->index(['project_id', 'product_attribute_id', 'product_attribute_value_id'], 'product_attribute_filter_index');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('product_attribute_product_value');
        Schema::dropIfExists('product_variant_values');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('product_attribute_values');
        Schema::dropIfExists('product_attributes');
    }
};
