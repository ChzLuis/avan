<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * El sorteo ("rifas") sale de BIXO: es otro producto y vive en su propio
 * proyecto. Se retiro todo su codigo (controlador, modelos, rutas, pantallas,
 * el bot de Meta que lo atendia) y estas dos tablas quedan sin ningun lector.
 *
 * Se eliminan porque en produccion nunca tuvieron una fila (verificado el
 * 2026-09-17: `rifas` = 0, `rifa_ventas` = 0). Si alguna instalacion las
 * tuviera con datos, exportarlos ANTES de correr esta migracion: no hay
 * vuelta atras.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Primero la hija (tiene la clave foranea), luego la madre.
        Schema::dropIfExists('rifa_ventas');
        Schema::dropIfExists('rifas');
    }

    public function down(): void
    {
        // Sin vuelta atras a proposito: el esquema original esta en las
        // migraciones de 2026-04 (create_rifas_table, create_rifa_ventas_table)
        // y el producto ya no existe en BIXO. Restaurar seria resucitarlo.
    }
};
