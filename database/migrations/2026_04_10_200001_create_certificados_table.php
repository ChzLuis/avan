<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('codigo', 20)->unique();          // CERT-XXXXX
            $table->string('alumno_nombre', 150);
            $table->string('alumno_dni', 20)->nullable();
            $table->string('alumno_email', 150)->nullable();
            $table->string('curso_nombre', 200);
            $table->string('curso_horas', 20)->nullable();   // "40 horas"
            $table->string('curso_nivel', 50)->nullable();   // "Básico", "Avanzado"
            $table->date('fecha_emision');
            $table->date('fecha_vencimiento')->nullable();
            $table->string('instructor', 150)->nullable();
            $table->string('institucion', 150)->nullable();  // nombre de la institución
            $table->enum('estado', ['emitido', 'revocado'])->default('emitido');
            $table->text('notas')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificados');
    }
};
