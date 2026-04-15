<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('number', 20)->nullable();
            $table->string('client_name', 150);
            $table->string('client_phone', 50)->nullable();
            $table->string('client_email', 150)->nullable();
            $table->string('business_name', 150)->nullable();
            $table->string('rubro', 100)->nullable();
            $table->string('city', 100)->nullable();
            $table->decimal('price', 10, 2)->default(490);
            $table->decimal('price_first', 10, 2)->default(245);
            $table->decimal('price_second', 10, 2)->default(245);
            $table->integer('valid_days')->default(5);
            $table->string('plat3_name', 150)->nullable();
            $table->text('plat3_features')->nullable();
            $table->string('plat4_name', 150)->nullable();
            $table->text('plat4_features')->nullable();
            $table->text('extra_notes')->nullable();
            $table->string('status', 30)->default('draft');
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proposals');
    }
};
