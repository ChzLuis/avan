<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Dos representaciones impresas del mismo comprobante: la moderna de siempre y
 * la clásica encuadrada. El negocio elige con `invoice_template`.
 *
 * Lo que se protege: que cambiar de plantilla NO altere nada fiscal —
 * denominación, número, RUC, importe en letras, QR y hash— y que el
 * comprobante siga cuadrando.
 */
class FacturaPlantillaClasicaTest extends TestCase
{
    use RefreshDatabase;

    private function factura(string $plantilla): array
    {
        $project = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Ferretería Clásica', 'slug' => 'fc-'.uniqid(), 'is_active' => true,
            'phone' => '999111222',
        ]);
        $project->settings()->createMany([
            ['key' => 'invoice_template', 'value' => $plantilla],
            ['key' => 'ruc', 'value' => '20117431615'],
            ['key' => 'razon_social', 'value' => 'FERRETERÍA CLÁSICA S.A.C.'],
            ['key' => 'logo_url', 'value' => 'logos/10/logo-de-prueba.png'],
            ['key' => 'cuentas_bancarias', 'value' => "BCP 191-0151126-1-34\nBBVA 0011-0659-0100001814"],
        ]);

        $invoice = Invoice::create([
            'project_id' => $project->id, 'type' => 'factura',
            'serie' => 'F022', 'correlativo' => '4512', 'numero' => 'F022-4512',
            'emisor_razon_social' => 'FERRETERÍA CLÁSICA S.A.C.',
            'emisor_ruc' => '20117431615',
            'emisor_direccion' => 'AV. REP. DE ARGENTINA NRO. 245',
            'client_name' => 'ELECTRICAL ENGINEERING SOLUTIONS S.A.C.',
            'client_doc_type' => 'RUC', 'client_doc_number' => '20611746491',
            'client_address' => 'CAL. JOSE OLAYA NRO. 351',
            'subtotal' => 953.70, 'igv' => 171.67, 'total' => 1125.37,
            'currency' => 'USD', 'status' => 'issued',
            'issue_date' => now(), 'payment_method' => 'Contado',
        ]);
        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'description' => 'AUTOMOTRIZ GPT-16 AWG', 'unit' => 'NIU',
            'quantity' => 35, 'unit_price' => 34.06, 'discount' => 20,
            'igv_amount' => 171.67, 'total' => 1125.37,
        ]);

        return [$project, $invoice->fresh('items')];
    }

    private function html(Project $project, Invoice $invoice): string
    {
        return view(
            (string) $project->setting('invoice_template') === 'clasico' ? 'invoices.pdf-clasico' : 'invoices.pdf',
            compact('project', 'invoice')
        )->render();
    }

    /** La clásica pinta las columnas y el recuadro de totales del formato. */
    public function test_la_clasica_trae_el_formato_encuadrado(): void
    {
        [$project, $invoice] = $this->factura('clasico');

        $html = $this->html($project, $invoice);

        // Tres cambios de 2026-09-03 al compactar la hoja, todos con el mismo
        // criterio: una columna que sale siempre vacia solo roba ancho.
        //  - "Valor Venta" se retiro: repetia el importe sin IGV incluido;
        //  - "% Desc." solo aparece si algun item lleva descuento;
        //  - "Precio Unitario" va partido con un <br> en medio;
        //  - "Código" solo se pinta si algun item trae SKU.
        // Las condicionales tienen su propio test ("sin descuentos no salen
        // las columnas"); aqui se exige lo que sale SIEMPRE.
        foreach (['UM', 'Precio<br>Unitario', 'Importe',
                  'Total importe', 'Op. gravada', 'Op. inafecta',
                  'Op. exonerada', 'Op. gratuitas', 'Precio venta'] as $columna) {
            $this->assertStringContainsString($columna, $html, "Falta «{$columna}».");
        }
        // `MontoEnLetras::de()` ya devuelve "SON ...": la plantilla no debe
        // anteponer otro "SON:" o sale duplicado.
        $this->assertMatchesRegularExpression('/SON [A-ZÁÉÍÓÚÑ]/u', $html, 'Falta el importe en letras.');
        $this->assertStringNotContainsString('SON: SON', $html, 'El "SON" sale duplicado.');

        // La columna de codigo se gana su sitio: sin SKU en ningun item, no
        // se pinta. Reservarle ancho en blanco estiraba toda la tabla.
        $this->assertStringNotContainsString('>Código<', $html, 'Sin SKU no debe salir la columna Código.');
    }

    /** La moderna sigue disponible y es la de por defecto. */
    public function test_la_moderna_sigue_siendo_la_predeterminada(): void
    {
        [$project, $invoice] = $this->factura('');

        $html = $this->html($project, $invoice);

        $this->assertStringContainsString('TOTAL', $html);
        $this->assertStringNotContainsString('Op. gratuitas', $html, 'Ese recuadro es de la clásica.');
    }

    /** Cambiar de plantilla NO toca nada fiscal. */
    public function test_lo_fiscal_es_identico_en_las_dos(): void
    {
        [$p1, $i1] = $this->factura('clasico');
        [$p2, $i2] = $this->factura('');

        $clasica = $this->html($p1, $i1);
        $moderna = $this->html($p2, $i2);

        foreach (['FACTURA ELECTRÓNICA', 'F022-4512', '20117431615',
                  'ELECTRICAL ENGINEERING SOLUTIONS S.A.C.', '20611746491'] as $dato) {
            $this->assertStringContainsString($dato, $clasica, "La clásica pierde «{$dato}».");
            $this->assertStringContainsString($dato, $moderna, "La moderna pierde «{$dato}».");
        }
        foreach ([$clasica, $moderna] as $html) {
            // El QR ya no viene de un servicio externo: se genera en la hoja.
            $this->assertStringContainsString('data-qr="', $html, 'Falta el QR normado.');
            $this->assertStringContainsString('Representación impresa', $html);
        }
    }

    /** El logo del negocio tiene que salir impreso, no su nombre en texto. */
    public function test_la_factura_sale_con_el_logo(): void
    {
        [$project, $invoice] = $this->factura('clasico');

        $html = $this->html($project, $invoice);

        $this->assertStringContainsString('logos/10/logo-de-prueba.png', $html, 'El logo no se imprime.');
        $this->assertMatchesRegularExpression('/<img[^>]+logos\/10\/logo-de-prueba\.png/', $html,
            'El logo debe ir en una etiqueta <img>, no como texto.');
    }

    /** Sin descuentos, esas dos columnas no se imprimen. */
    public function test_sin_descuentos_no_salen_las_columnas(): void
    {
        [$project, $invoice] = $this->factura('clasico');
        $invoice->items->each(fn ($i) => $i->update(['discount' => 0]));

        $html = $this->html($project, $invoice->fresh('items'));

        $this->assertStringNotContainsString('% Desc.', $html, 'Sin descuentos no debe salir la columna.');
        $this->assertStringNotContainsString('Total descuento', $html);
        $this->assertStringContainsString('Op. gravada', $html, 'El resto del recuadro sigue.');
    }

    /** El descuento es un porcentaje: la clásica muestra el importe descontado. */
    public function test_la_clasica_calcula_el_descuento_desde_el_porcentaje(): void
    {
        [$project, $invoice] = $this->factura('clasico');

        $html = $this->html($project, $invoice);

        // 35 × 34.06 = 1,192.10 de importe; 20 % = 238.42 de descuento.
        $this->assertStringContainsString('1,192.10', $html, 'Importe bruto de la línea.');
        $this->assertStringContainsString('20.00%', $html, 'Porcentaje de descuento.');
        $this->assertStringContainsString('238.42', $html, 'Descuento calculado.');
    }
}
