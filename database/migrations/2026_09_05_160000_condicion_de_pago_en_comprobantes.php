<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Condicion de pago del comprobante.
 *
 * El formulario ofrecia "Contado / Credito" desde el principio, pero el valor
 * no se guardaba en ningun sitio y el XML declaraba SIEMPRE "Contado" a SUNAT.
 * Una factura a credito declarada al contado es un dato fiscal falso y ademas
 * deja al negocio sin la cuota que SUNAT exige para las ventas a credito.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('invoices', 'payment_condition')) {
            return;
        }
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('payment_condition', 20)->default('contado')->after('payment_method');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('payment_condition');
        });
    }
};
