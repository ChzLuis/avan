<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Modules\Ventas\Models\Quote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El enlace del cliente y el PDF son EL MISMO documento.
 *
 * No lo eran: el portal decia "Pendiente" y el papel que el cliente se
 * descargaba decia "Enviada" para la misma cotizacion, porque cada uno tenia
 * su propio mapa de estados. Y el boton "Imprimir / PDF" del portal hacia
 * `window.print()` sobre la pagina, asi que producia un TERCER documento: otra
 * maqueta, sin columna de descuento y sin la nota legal.
 */
class QuoteDocumentoUnicoTest extends TestCase
{
    use RefreshDatabase;

    private function cotizacion(string $estado = 'sent'): array
    {
        $project = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Doc QA', 'slug' => 'doc-qa', 'category' => 'retail', 'is_active' => true,
        ]);
        $quote = Quote::create([
            'project_id' => $project->id, 'client_name' => 'PRUEBA', 'status' => $estado,
            'total' => 3299, 'token' => str()->random(48), 'valid_until' => now()->addDays(15),
        ]);
        $quote->items()->create(['description' => 'All-in-One HP 24" i5 8GB',
            'price' => 3299, 'quantity' => 1, 'discount' => 0]);

        return [$project, $quote];
    }

    public function test_el_enlace_y_el_pdf_dicen_el_mismo_estado(): void
    {
        [$project, $quote] = $this->cotizacion('sent');

        $portal = $this->get("/b/{$project->slug}/c/{$quote->token}")->assertOk();
        $pdf    = $this->get("/b/{$project->slug}/c/{$quote->token}/pdf")->assertOk();

        // Un solo vocabulario para el cliente.
        $portal->assertSee('Pendiente');
        $pdf->assertSee('Pendiente');
        $pdf->assertDontSee('Enviada');
    }

    public function test_el_pdf_publico_es_el_mismo_documento_que_exporta_el_negocio(): void
    {
        [$project, $quote] = $this->cotizacion();
        // Una linea con descuento: la columna Dscto. solo existe cuando algun
        // item lo tiene, igual que en el comprobante (misma familia visual).
        $quote->items()->create(['description' => 'Mouse inalámbrico',
            'price' => 100, 'quantity' => 1, 'discount' => 10]);

        $res = $this->get("/b/{$project->slug}/c/{$quote->token}/pdf")->assertOk();

        $res->assertSee($quote->etiqueta)                       // COT-00001
            ->assertSee('PRUEBA')
            ->assertSee('All-in-One HP 24&quot; i5 8GB', false)
            ->assertSee('3,299.00')
            ->assertSee('Dscto.')                                // la columna que el portal no tenia
            ->assertSee('90.00')                                 // 100 − 10%, por LineMath
            ->assertSee('no constituye comprobante de pago');    // la nota legal
    }

    public function test_el_portal_enlaza_al_documento_y_no_imprime_la_pagina(): void
    {
        [$project, $quote] = $this->cotizacion();

        $this->get("/b/{$project->slug}/c/{$quote->token}")
            ->assertOk()
            ->assertSee(route('portal.quote.pdf', [$project->slug, $quote->token]), false)
            ->assertDontSee('onclick="window.print()"', false);
    }

    public function test_el_pdf_publico_exige_el_token_correcto(): void
    {
        [$project, $quote] = $this->cotizacion();

        $this->get("/b/{$project->slug}/c/" . str()->random(48) . "/pdf")->assertNotFound();
    }
}
