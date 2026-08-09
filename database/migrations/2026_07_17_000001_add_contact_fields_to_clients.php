<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Campos de ficha de contacto completa (estilo MERKADO) editables desde la extensión. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $t) {
            $cols = [
                'sexo'             => fn () => $t->string('sexo', 20)->nullable(),
                'fecha_nacimiento' => fn () => $t->date('fecha_nacimiento')->nullable(),
                'idioma'           => fn () => $t->string('idioma', 30)->nullable(),
                'pais'             => fn () => $t->string('pais', 60)->nullable(),
                'ciudad'           => fn () => $t->string('ciudad', 80)->nullable(),
                'provincia'        => fn () => $t->string('provincia', 80)->nullable(),
                'direccion'        => fn () => $t->string('direccion')->nullable(),
                'cargo'            => fn () => $t->string('cargo', 80)->nullable(),
                'valor_negocio'    => fn () => $t->decimal('valor_negocio', 12, 2)->nullable(),
            ];
            foreach ($cols as $name => $add) {
                if (!Schema::hasColumn('clients', $name)) $add();
            }
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $t) {
            $t->dropColumn(['sexo', 'fecha_nacimiento', 'idioma', 'pais', 'ciudad', 'provincia', 'direccion', 'cargo', 'valor_negocio']);
        });
    }
};
