<?php

namespace Tests\Feature;

use App\Models\Module;
use App\Models\Project;
use App\Models\User;
use App\Modules\Crm\Models\WaCanal;
use App\Support\Productos;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * El CRM como producto propio: un negocio puede nacer "solo CRM" desde el
 * registro publico, el portal /bixocrm exige el producto contratado, y el
 * asistente conecta la linea con Meta probando las credenciales ANTES de
 * guardarlas.
 */
class CrmProductoTest extends TestCase
{
    use RefreshDatabase;

    private function datosRegistro(array $extra = []): array
    {
        return array_merge([
            'negocio' => 'Ferretería Lima', 'nombre' => 'Rosa', 'email' => 'rosa@ferreteria.pe',
            'whatsapp' => '999111222', 'password' => 'secreto123', 'password_confirmation' => 'secreto123',
        ], $extra);
    }

    public function test_el_registro_crea_usuario_y_negocio_solo_crm_y_entra_al_asistente(): void
    {
        $this->get('/bixocrm/registro')->assertOk()->assertViewIs('crm::comunicaciones.auth.registro');

        $this->post('/bixocrm/registro', $this->datosRegistro())
            ->assertRedirect(route('bixocrm.conectar'));

        $p = Project::where('name', 'Ferretería Lima')->firstOrFail();
        $this->assertSame('rosa@ferreteria.pe', $p->owner->email);
        $this->assertTrue(Productos::contratado($p, 'crm'));
        $this->assertFalse($p->hasModule('store'), 'Nacio solo CRM.');
        $this->assertFalse($p->hasModule('invoices'));
        $this->assertAuthenticated();
        $this->assertSame($p->id, session('comunicaciones_project_id'));
        $this->assertDatabaseHas('employees', ['project_id' => $p->id, 'user_id' => $p->owner_id]);
    }

    public function test_el_registro_rechaza_correo_repetido_y_contrasena_sin_confirmar(): void
    {
        User::factory()->create(['email' => 'rosa@ferreteria.pe']);

        $this->from('/bixocrm/registro')->post('/bixocrm/registro', $this->datosRegistro())
            ->assertRedirect('/bixocrm/registro')->assertSessionHasErrors('email');

        $this->post('/bixocrm/registro', $this->datosRegistro(['email' => 'otra@x.pe', 'password_confirmation' => 'otra']))
            ->assertSessionHasErrors('password');

        $this->assertSame(0, Project::count(), 'Ningun negocio a medias.');
    }

    public function test_el_registro_publico_lleva_limite_de_envios(): void
    {
        $ruta = collect(\Illuminate\Support\Facades\Route::getRoutes())->first(fn ($r) => $r->getName() === 'bixocrm.registro.post');

        $this->assertTrue(collect($ruta->gatherMiddleware())->contains(fn ($m) => str_starts_with($m, 'throttle')));
    }

    public function test_el_portal_no_abre_a_un_negocio_sin_el_producto_crm(): void
    {
        $user = User::factory()->create();
        $sinCrm = Project::create(['owner_id' => $user->id, 'name' => 'Sin CRM', 'slug' => 'sin-crm-' . uniqid(), 'is_active' => true]);
        // Solo `clients` (lo tiene todo negocio por su panel de clientes) NO es el CRM.
        $m = Module::firstOrCreate(['key' => 'clients'], ['name' => 'clients', 'is_active' => true]);
        $sinCrm->modules()->syncWithoutDetaching([$m->id => ['is_active' => true]]);

        $this->actingAs($user)->withSession(['comunicaciones_project_id' => $sinCrm->id])
            ->get('/bixocrm')
            ->assertRedirect(route('bixocrm.login'))
            ->assertSessionHasErrors('email');

        Productos::activar($sinCrm, 'crm');
        $this->actingAs($user)->withSession(['comunicaciones_project_id' => $sinCrm->id])
            ->get('/bixocrm')
            ->assertOk();
    }

    /** Sin sesion, cada portal vuelve a SU login, no al del panel (bug previo al producto). */
    public function test_sin_sesion_cada_portal_vuelve_a_su_propio_login(): void
    {
        $this->get('/bixocrm')->assertRedirect(route('bixocrm.login'));
        $this->get('/bixocrm/conectar')->assertRedirect(route('bixocrm.login'));
        $this->get('/bixosales/pedidos')->assertRedirect(route('bixosales.login'));
        $this->get('/bixoadmin/products')->assertRedirect(route('bixoadmin.login'));
    }

    /** Con varios negocios, el login pregunta a cual entrar; solo abren los que tienen CRM. */
    public function test_con_varios_negocios_el_login_pregunta_y_solo_deja_elegir_los_que_tienen_crm(): void
    {
        $user = User::factory()->create(['password' => \Illuminate\Support\Facades\Hash::make('clave123')]);
        $a = Project::create(['owner_id' => $user->id, 'name' => 'Con CRM A', 'slug' => 'crm-a-' . uniqid(), 'is_active' => true]);
        $b = Project::create(['owner_id' => $user->id, 'name' => 'Con CRM B', 'slug' => 'crm-b-' . uniqid(), 'is_active' => true]);
        $sin = Project::create(['owner_id' => $user->id, 'name' => 'Sin CRM', 'slug' => 'sin-' . uniqid(), 'is_active' => true]);
        Productos::activar($a, 'crm'); Productos::activar($b, 'crm');

        $this->post('/bixocrm/login', ['email' => $user->email, 'password' => 'clave123'])
            ->assertRedirect(route('bixocrm.elegir'));
        $this->assertNull(session('comunicaciones_project_id'));

        $this->get('/bixocrm/elegir')->assertOk()->assertViewIs('crm::comunicaciones.auth.elegir')
            ->assertSee('Con CRM A')->assertSee('Sin CRM')->assertSee('Sin CRM contratado');

        $this->post('/bixocrm/elegir', ['project_id' => $sin->id])->assertForbidden();
        $this->post('/bixocrm/elegir', ['project_id' => $b->id])->assertRedirect(route('bixocrm.bandeja'));
        $this->assertSame($b->id, session('comunicaciones_project_id'));
    }

    /** Con un solo negocio con CRM, se entra directo. */
    public function test_con_un_solo_negocio_con_crm_el_login_entra_directo(): void
    {
        $user = User::factory()->create(['password' => \Illuminate\Support\Facades\Hash::make('clave123')]);
        $a = Project::create(['owner_id' => $user->id, 'name' => 'Unico', 'slug' => 'unico-' . uniqid(), 'is_active' => true]);
        Productos::activar($a, 'crm');
        Project::create(['owner_id' => $user->id, 'name' => 'Sin CRM', 'slug' => 'sin-' . uniqid(), 'is_active' => true]);

        $this->post('/bixocrm/login', ['email' => $user->email, 'password' => 'clave123'])
            ->assertRedirect(route('bixocrm.bandeja'));
        $this->assertSame($a->id, session('comunicaciones_project_id'));
    }

    private function negocioCrm(): array
    {
        $user = User::factory()->create();
        $p = Project::create(['owner_id' => $user->id, 'name' => 'CRM QA', 'slug' => 'crm-qa-' . uniqid(), 'is_active' => true]);
        Productos::activar($p, 'crm');

        return [$user, $p];
    }

    public function test_el_asistente_se_pinta_y_la_bandeja_avisa_si_no_hay_linea(): void
    {
        [$user, $p] = $this->negocioCrm();
        $s = $this->actingAs($user)->withSession(['comunicaciones_project_id' => $p->id]);

        $s->get('/bixocrm')->assertOk()->assertSee('Conecta tu WhatsApp');
        $s->get('/bixocrm/conectar')->assertOk()->assertViewIs('crm::comunicaciones.conectar')->assertSee(url('/api/whatsapp/webhook'));
    }

    public function test_probar_consulta_a_meta_y_no_guarda_nada(): void
    {
        [$user, $p] = $this->negocioCrm();
        Http::fake([
            'graph.facebook.com/*/123*' => Http::response(['display_phone_number' => '+51 999 111 222', 'verified_name' => 'Ferretería Lima'], 200),
            'graph.facebook.com/*/999*' => Http::response(['error' => ['message' => 'Invalid OAuth access token.', 'code' => 190]], 401),
        ]);
        $s = $this->actingAs($user)->withSession(['comunicaciones_project_id' => $p->id]);

        $s->postJson('/bixocrm/conectar/probar', ['phone_number_id' => '123', 'access_token' => 'TOKEN'])
            ->assertOk()->assertJson(['ok' => true, 'numero' => '+51 999 111 222', 'nombre' => 'Ferretería Lima']);

        $s->postJson('/bixocrm/conectar/probar', ['phone_number_id' => '999', 'access_token' => 'MALO'])
            ->assertStatus(422)->assertJson(['ok' => false, 'error' => 'Invalid OAuth access token.']);

        $this->assertSame(0, WaCanal::count(), 'Probar no crea el canal.');
        Http::assertSent(fn ($req) => $req->hasHeader('Authorization', 'Bearer TOKEN') && str_contains($req->url(), '/123'));
    }

    public function test_el_asistente_guarda_el_canal_con_sus_secretos_cifrados(): void
    {
        [$user, $p] = $this->negocioCrm();

        $this->actingAs($user)->withSession(['comunicaciones_project_id' => $p->id])
            ->postJson('/bixocrm/canales', [
                'nombre' => 'Línea principal', 'tipo' => 'bixo', 'phone_number_id' => '123',
                'access_token' => 'TOKEN', 'app_secret' => 'SECRETO', 'verify_token' => 'bixo_abc', 'color' => '#25d366',
            ])->assertOk()->assertJson(['ok' => true]);

        $canal = WaCanal::where('project_id', $p->id)->firstOrFail();
        $this->assertTrue($canal->conectadoAMeta());
        $this->assertSame('SECRETO', $canal->app_secret);
        $this->assertNotSame('SECRETO', \Illuminate\Support\Facades\DB::table('wa_canales')->value('app_secret'), 'En la base va cifrado.');
    }
}
