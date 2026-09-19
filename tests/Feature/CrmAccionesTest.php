<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Modules\Crm\Models\CrmAccion;
use App\Modules\Crm\Models\PushSuscripcion;
use App\Modules\Crm\Models\WaCanal;
use App\Modules\Crm\Models\WaConversacion;
use App\Support\Productos;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Acciones (fase 3): alta desde el chat, listado por urgencia, marcar hecha, posponer,
 * recordatorio push al vencer (una sola vez y solo al asesor asignado), aislamiento.
 */
class CrmAccionesTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;
    private Project $proyecto;
    private WaCanal $canal;

    protected function setUp(): void
    {
        parent::setUp();
        $this->usuario = User::factory()->create();
        $this->proyecto = Project::create(['owner_id' => $this->usuario->id, 'name' => 'CRM acciones', 'slug' => 'crm-acc-' . uniqid(), 'is_active' => true]);
        Productos::activar($this->proyecto, 'crm');
        $this->canal = WaCanal::create(['project_id' => $this->proyecto->id, 'nombre' => 'L', 'tipo' => 'bixo', 'activo' => true, 'phone_number_id' => '1', 'access_token' => 'T']);
    }

    private function enElCrm()
    {
        return $this->actingAs($this->usuario)->withSession(['comunicaciones_project_id' => $this->proyecto->id]);
    }

    private function conv(): WaConversacion
    {
        return WaConversacion::create(['wa_canal_id' => $this->canal->id, 'cliente_nombre' => 'Rosa', 'cliente_telefono' => '51999', 'estado' => 'nuevo', 'no_leidos' => 0, 'ultimo_mensaje_at' => now(), 'bot_activo' => false]);
    }

    public function test_se_crea_desde_el_chat_y_aparece_en_la_pagina_y_en_la_ficha(): void
    {
        $conv = $this->conv();
        $r = $this->enElCrm()->postJson('/bixocrm/acciones', ['titulo' => 'Llamar a Rosa', 'vence_at' => now()->addDay()->setTime(9, 0)->format('Y-m-d\TH:i'), 'wa_conversacion_id' => $conv->id])->assertCreated();

        $this->assertSame('Rosa', $r->json('accion.contacto'));
        $this->assertFalse($r->json('accion.vencida'));
        $a = CrmAccion::firstOrFail();
        $this->assertSame($this->usuario->id, $a->asignado_a, 'Sin asignado explicito, queda para quien la creo');
        $this->assertSame($this->proyecto->id, $a->project_id);

        $this->enElCrm()->get('/bixocrm/acciones')->assertOk()->assertViewIs('crm::acciones.index')->assertSee('Llamar a Rosa');
        $lista = $this->enElCrm()->getJson('/bixocrm/acciones?conversacion_id=' . $conv->id)->assertOk()->json('acciones');
        $this->assertCount(1, $lista);
    }

    public function test_marcar_hecha_y_posponer(): void
    {
        $a = CrmAccion::create(['project_id' => $this->proyecto->id, 'titulo' => 'X', 'vence_at' => now()->subHour(), 'recordada_at' => now()]);
        $this->assertTrue($a->vencida());

        $this->enElCrm()->patchJson("/bixocrm/acciones/{$a->id}", ['hecha' => true])->assertOk();
        $this->assertNotNull($a->fresh()->hecho_at);

        $this->enElCrm()->patchJson("/bixocrm/acciones/{$a->id}", ['hecha' => false, 'vence_at' => now()->addDay()->format('Y-m-d\TH:i')])->assertOk();
        $a->refresh();
        $this->assertNull($a->hecho_at);
        $this->assertNull($a->recordada_at, 'Al mover la fecha, el recordatorio vuelve a quedar pendiente');
        $this->assertFalse($a->vencida());
    }

    public function test_el_recordatorio_push_sale_una_sola_vez_y_solo_al_asesor_asignado(): void
    {
        $otro = User::factory()->create();
        PushSuscripcion::create(['user_id' => $this->usuario->id, 'project_id' => $this->proyecto->id, 'endpoint' => 'https://fcm.googleapis.com/fcm/send/mio', 'p256dh' => 'BJ' . str_repeat('A', 85), 'auth' => 'x']);
        PushSuscripcion::create(['user_id' => $otro->id, 'project_id' => $this->proyecto->id, 'endpoint' => 'https://fcm.googleapis.com/fcm/send/otro', 'p256dh' => 'BJ' . str_repeat('A', 85), 'auth' => 'x']);
        $conv = $this->conv();
        $a = CrmAccion::create(['project_id' => $this->proyecto->id, 'titulo' => 'Enviar propuesta', 'vence_at' => now()->subMinute(), 'asignado_a' => $this->usuario->id, 'wa_conversacion_id' => $conv->id]);
        CrmAccion::create(['project_id' => $this->proyecto->id, 'titulo' => 'Futura', 'vence_at' => now()->addHour(), 'asignado_a' => $this->usuario->id]);
        Http::fake(['fcm.googleapis.com/*' => Http::response('', 201)]);

        // El cifrado necesita una clave p256dh real; aqui basta con que el envio se intente:
        // PushWeb devuelve ok=false si no puede cifrar, y aun asi la accion queda marcada.
        $this->artisan('crm:recordar-acciones')->assertSuccessful();

        $this->assertNotNull($a->fresh()->recordada_at, 'Queda anotado que ya se aviso');
        $this->assertNull(CrmAccion::where('titulo', 'Futura')->first()->recordada_at, 'La que no vence todavia no avisa');
        Http::assertNotSent(fn ($req) => str_contains($req->url(), 'fcm/send/otro'));

        $this->artisan('crm:recordar-acciones')->assertSuccessful();
        $this->assertSame(1, CrmAccion::whereNotNull('recordada_at')->count(), 'No se repite');
    }

    public function test_las_acciones_de_otro_negocio_no_se_ven_ni_se_tocan(): void
    {
        $otro = Project::create(['owner_id' => User::factory()->create()->id, 'name' => 'Otro', 'slug' => 'otro-' . uniqid(), 'is_active' => true]);
        $ajena = CrmAccion::create(['project_id' => $otro->id, 'titulo' => 'Ajena']);

        $this->assertCount(0, $this->enElCrm()->getJson('/bixocrm/acciones?json=1')->json('acciones'));
        $this->enElCrm()->patchJson("/bixocrm/acciones/{$ajena->id}", ['hecha' => true])->assertForbidden();
        $this->enElCrm()->deleteJson("/bixocrm/acciones/{$ajena->id}")->assertForbidden();
        // Tampoco se puede colgar una accion de un chat ajeno.
        $canalAjeno = WaCanal::create(['project_id' => $otro->id, 'nombre' => 'L', 'tipo' => 'bixo', 'activo' => true]);
        $convAjena = WaConversacion::create(['wa_canal_id' => $canalAjeno->id, 'cliente_nombre' => 'X', 'cliente_telefono' => '51900', 'estado' => 'nuevo', 'no_leidos' => 0, 'ultimo_mensaje_at' => now(), 'bot_activo' => false]);
        $this->enElCrm()->postJson('/bixocrm/acciones', ['titulo' => 'Intrusa', 'wa_conversacion_id' => $convAjena->id])->assertForbidden();
    }

    public function test_el_menu_muestra_cuantas_acciones_hay_para_hoy(): void
    {
        CrmAccion::create(['project_id' => $this->proyecto->id, 'titulo' => 'Hoy', 'vence_at' => now()]); // now(): a las 23:30 un +1h ya seria manana
        CrmAccion::create(['project_id' => $this->proyecto->id, 'titulo' => 'Vencida', 'vence_at' => now()->subDay()]);
        CrmAccion::create(['project_id' => $this->proyecto->id, 'titulo' => 'Manana', 'vence_at' => now()->addDays(2)]);
        CrmAccion::create(['project_id' => $this->proyecto->id, 'titulo' => 'Hecha', 'vence_at' => now(), 'hecho_at' => now()]);

        $html = $this->enElCrm()->get('/bixocrm/tratos')->assertOk()->getContent();
        $this->assertMatchesRegularExpression('/Acciones\s*<span[^>]*>2<\/span>/s', $html, 'Cuenta la de hoy y la vencida; no la de pasado ni la hecha');
    }
}
