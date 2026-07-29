<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('contact_messages', function (Blueprint $table) {
            $table->id(); $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120); $table->string('phone', 40)->nullable(); $table->string('email', 160)->nullable();
            $table->string('subject', 180)->nullable(); $table->text('message'); $table->boolean('privacy_accepted')->default(false);
            $table->string('status', 30)->default('new'); $table->timestamps();
        });
        Schema::create('complaints', function (Blueprint $table) {
            $table->id(); $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('code', 40)->unique(); $table->string('consumer_name', 160); $table->string('document_type', 30); $table->string('document_number', 40);
            $table->string('address', 255)->nullable(); $table->string('phone', 40)->nullable(); $table->string('email', 160);
            $table->string('product_or_service', 255); $table->decimal('amount', 12, 2)->nullable(); $table->enum('type', ['reclamo','queja']);
            $table->text('detail'); $table->text('request'); $table->boolean('terms_accepted'); $table->string('status', 30)->default('received'); $table->timestamps();
            $table->index(['project_id', 'status']);
        });
    }
    public function down(): void { Schema::dropIfExists('complaints'); Schema::dropIfExists('contact_messages'); }
};
