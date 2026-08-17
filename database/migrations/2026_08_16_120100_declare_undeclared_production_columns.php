<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Columnas que existían en producción sin ninguna migración que las creara.
 *
 * Se detectaron comparando el esquema real del VPS contra el que generan las
 * migraciones desde cero: se habían añadido a mano. Consecuencia: un entorno
 * nuevo (o los tests, que corren sobre SQLite en memoria) levantaba sin ellas y
 * reventaba, aunque producción funcionara. Aquí solo se DECLARAN; en producción
 * no cambian nada porque ya están.
 *
 *   categories.slug            -> el modelo Category lo escribe al guardar
 *   projects.domain_status     -> estado del alta de dominio
 *   projects.domain_checked_at -> última verificación del dominio
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            if (!Schema::hasColumn('categories', 'slug')) {
                $table->string('slug', 160)->nullable()->after('name');
            }
        });

        Schema::table('projects', function (Blueprint $table) {
            if (!Schema::hasColumn('projects', 'domain_status')) {
                $table->string('domain_status', 30)->nullable();
            }
            if (!Schema::hasColumn('projects', 'domain_checked_at')) {
                $table->timestamp('domain_checked_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        // No se revierte: estas columnas llevan datos vivos en producción y la
        // migración solo existe para declarar lo que ya estaba.
    }
};
