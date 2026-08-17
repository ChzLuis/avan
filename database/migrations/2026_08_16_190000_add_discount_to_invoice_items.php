<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * F4 — El descuento por linea llega al comprobante.
 *
 * `invoice_items` guardaba `unit_price` y `total` pero no el descuento, asi
 * que una linea rebajada dejaba `unit_price * quantity != total`: un descuadre
 * que un comprobante fiscal no puede permitirse y que nadie podria explicar
 * mirando el documento. Por eso `convertirPortal` respondia 422 a cualquier
 * cotizacion con descuento — bloqueando la venta, no solo la factura.
 *
 * Mismo criterio que `quote_items` y `order_items`: porcentaje 0-100 con dos
 * decimales, para que la linea se lea igual en los tres sitios.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('invoice_items', 'discount')) {
            return;
        }

        Schema::table('invoice_items', function (Blueprint $table) {
            $table->decimal('discount', 5, 2)->default(0)->after('unit_price');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('invoice_items', 'discount')) {
            return;
        }
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropColumn('discount');
        });
    }
};
