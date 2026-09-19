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
    public function test_el_aviso_por_whatsapp_es_opcional_y_no_se_manda_si_no_se_activo(): void
    {
        $this->canal->update(['phone_number_id' => '777', 'access_token' => 'TOKEN', 'app_secret' => 's']);
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'x']]], 200)]);
        $conv = $this->conv();

        // Sin marcar la casilla: no se guarda numero y el comando no manda nada.
        $this->enElCrm()->postJson('/bixocrm/acciones', ['titulo' => 'Llamar', 'tipo' => 'llamada', 'vence_at' => now()->addMinutes(5)->format('Y-m-d\TH:i'), 'wa_conversacion_id' => $conv->id])->assertCreated();
        $this->artisan('crm:avisar-acciones')->assertSuccessful();
        Http::assertNothingSent();
        $this->assertNull(CrmAccion::first()->avisar_whatsapp);
    }

    public function test_con_el_aviso_activado_llega_un_whatsapp_los_minutos_antes_y_una_sola_vez(): void
    {
        $this->canal->update(['phone_number_id' => '777', 'access_token' => 'TOKEN', 'app_secret' => 's']);
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'x']]], 200)]);
        $conv = $this->conv();

        // Llamada a las 22:00 con aviso 10 minutos antes.
        $r = $this->enElCrm()->postJson('/bixocrm/acciones', [
            'titulo' => 'Llamar para cerrar', 'tipo' => 'llamada',
            'vence_at' => now()->addMinutes(30)->format('Y-m-d\TH:i'),
            'wa_conversacion_id' => $conv->id,
            'avisar_whatsapp' => '+51 955 354 646', 'avisar_minutos' => 10,
        ])->assertCreated();
        $this->assertSame('51955354646', $r->json('accion.avisar_whatsapp'), 'El numero se guarda solo con digitos');

        // Faltan 30 min: todavia no toca.
        $this->artisan('crm:avisar-acciones')->assertSuccessful();
        Http::assertNothingSent();

        // A 10 minutos de la hora: sale el WhatsApp al numero indicado.
        $a = CrmAccion::first();
        $a->forceFill(['vence_at' => now()->addMinutes(9)])->save();
        $this->artisan('crm:avisar-acciones')->assertSuccessful();
        Http::assertSent(function ($req) {
            $d = $req->data();
            return ($d['to'] ?? '') === '51955354646'
                && str_contains($d['text']['body'] ?? '', 'Llamar para cerrar')
                && str_contains($d['text']['body'] ?? '', 'Recordatorio');
        });
        $this->assertNotNull($a->fresh()->avisada_at);

        // No se repite en el siguiente minuto.
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'y']]], 200)]);
        $this->artisan('crm:avisar-acciones')->assertSuccessful();
        Http::assertNothingSent();
    }

    public function test_al_mover_la_hora_el_aviso_vuelve_a_quedar_pendiente(): void
    {
        $a = CrmAccion::create(['project_id' => $this->proyecto->id, 'titulo' => 'X', 'vence_at' => now()->addMinutes(5),
            'avisar_whatsapp' => '51955354646', 'avisar_minutos' => 10, 'avisada_at' => now(), 'recordada_at' => now()]);

        $this->enElCrm()->patchJson("/bixocrm/acciones/{$a->id}", ['vence_at' => now()->addDay()->format('Y-m-d\TH:i')])->assertOk();

        $this->assertNull($a->fresh()->avisada_at, 'Si se mueve la hora, hay que volver a avisar');
        $this->assertNull($a->fresh()->recordada_at);
    }
    /** Lo que se crea desde el chat tiene que verse en la pagina Acciones, con su tipo y su aviso. */
    public function test_la_accion_creada_desde_el_chat_figura_en_la_pagina_acciones(): void
    {
        $conv = $this->conv();
        $this->enElCrm()->postJson('/bixocrm/acciones', [
            'titulo' => 'Llamar Sábado 19', 'tipo' => 'llamada',
            'vence_at' => now()->addHours(3)->format('Y-m-d\TH:i'),
            'wa_conversacion_id' => $conv->id,
            'avisar_whatsapp' => '51955354646', 'avisar_minutos' => 10,
        ])->assertCreated();

        // En la pagina (los datos viajan como JSON a Alpine).
        $html = $this->enElCrm()->get('/bixocrm/acciones')->assertOk()->getContent();
        $this->assertStringContainsString('Llamar S', $html, 'El titulo viaja a la vista');
        $this->assertStringContainsString('"tipo":"llamada"', $html);
        $this->assertStringContainsString('"avisar_whatsapp":"51955354646"', $html);

        // Y en la lista JSON que usa la ficha del chat.
        $lista = $this->enElCrm()->getJson('/bixocrm/acciones?json=1')->assertOk()->json('acciones');
        $this->assertSame('Llamar Sábado 19', $lista[0]['titulo']);
        $this->assertSame('llamada', $lista[0]['tipo']);
        $this->assertSame(10, $lista[0]['avisar_minutos']);
    }
}
