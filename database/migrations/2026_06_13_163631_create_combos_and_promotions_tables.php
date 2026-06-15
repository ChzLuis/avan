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
        // Combos: agrupan productos/servicios con precio especial
        Schema::create('combos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->decimal('compare_price', 10, 2)->nullable(); // precio sin descuento
            $table->string('image_url')->nullable();
            $table->boolean('is_available')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Items de cada combo (productos o servicios)
        Schema::create('combo_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('combo_id')->constrained()->cascadeOnDelete();
            $table->string('item_type')->default('product'); // product | service | custom
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('custom_name')->nullable(); // si no hay producto vinculado
            $table->integer('quantity')->default(1);
            $table->timestamps();
        });

        // Promociones: descuentos con vigencia
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type')->default('percentage'); // percentage | fixed | combo
            $table->decimal('value', 10, 2)->default(0);   // % o monto fijo
            $table->string('applies_to')->default('all');  // all | category | product | combo
            $table->unsignedBigInteger('applies_to_id')->nullable();
            $table->string('coupon_code')->nullable();
            $table->integer('max_uses')->nullable();
            $table->integer('uses_count')->default(0);
            $table->decimal('min_order', 10, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotions');
        Schema::dropIfExists('combo_items');
        Schema::dropIfExists('combos');
    }
};
