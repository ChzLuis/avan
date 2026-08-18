<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Module;
use App\Models\Order;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * El componente Alpine no puede volcarse a la pantalla como texto.
 *
 * `x-data` es un ATRIBUTO HTML delimitado por comillas dobles. Una comilla
 * doble dentro de la expresion —aunque este dentro de un comentario de JS— lo
 * cierra ahi mismo: el navegador se traga el resto como atributos basura hasta
 * el primer '>' (que suele ser el de una funcion flecha `q => {`) y desde ese
 * punto escupe TODO el componente como texto plano sobre la interfaz.
 *
 * Ha pasado dos veces el mismo dia, la segunda en un comentario escrito tres
 * lineas debajo del aviso que lo prohibe. Recordarlo no funciono.
 *
 * La comprobacion va sobre el HTML RENDERIZADO y no sobre el .blade: un
 * comentario PHP dentro de `{{ Js::from(...) }}` lleva comillas dobles sin
 * ningun riesgo, porque se compila en el servidor y jamas llega al navegador.
 * Solo el resultado final dice la verdad.
 */
class AlpineAtributoIntactoTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['quotes.ver', 'quotes.crear', 'quotes.editar', 'quotes.eliminar',
                  'orders.ver', 'orders.editar', 'view-quotes', 'manage-quotes',
                  'view-orders', 'manage-orders'] as $p) {
            Permission::findOrCreate($p, 'web');
        }
        $this->project = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Alpine QA', 'slug' => 'alpine-qa', 'category' => 'retail', 'is_active' => true,
        ]);
        foreach (['orders', 'quotes'] as $key) {
            $m = Module::firstOrCreate(['key' => $key], ['name' => $key, 'is_active' => true]);
            $this->project->modules()->syncWithoutDetaching([$m->id => ['is_active' => true]]);
        }

        $rol = Role::findOrCreate('alpine_qa', 'web');
        $rol->syncPermissions(['quotes.ver', 'quotes.crear', 'quotes.editar', 'quotes.eliminar',
                               'orders.ver', 'orders.editar']);
        $u = User::factory()->create(['is_superadmin' => 0]);
        ProjectMember::create(['project_id' => $this->project->id, 'user_id' => $u->id, 'role' => 'viewer']);
        Employee::create(['project_id' => $this->project->id, 'user_id' => $u->id,
            'name' => 'U', 'spatie_role' => $rol->name, 'is_active' => 1]);
        $u->syncRoles([$rol->name]);
        $this->actingAs($u)->withSession([
            'comercial_project_id' => $this->project->id,
            'active_project_id'    => $this->project->id,
        ]);
    }

    public static function pantallasConAlpine(): array
    {
        return [
            'cotizaciones' => ['/bixosales/cotizaciones'],
            'pedidos'      => ['/bixosales/pedidos'],
        ];
    }

    /**
     * @dataProvider pantallasConAlpine
     */
    public function test_ningun_atributo_alpine_se_cierra_a_destiempo(string $ruta): void
    {
        // Con datos dentro: los campos del negocio tambien viajan al atributo.
        $q = Quote::create([
            'project_id' => $this->project->id, 'client_name' => 'Cliente "Comillas" SAC',
            'status' => 'sent', 'total' => '150.00', 'token' => str()->random(24),
            'notes' => 'Nota con "comillas" del usuario',
        ]);
        $q->items()->create(['description' => 'Item', 'price' => '150.00', 'quantity' => 1, 'discount' => 0]);
        Order::create([
            'project_id' => $this->project->id, 'client_name' => 'Cliente "X"',
            'status' => 'pending', 'total' => '150.00',
        ]);

        $html = $this->get($ruta)->assertSuccessful()->getContent();

        // Fuera <style> y <script>: ahi dentro `[x-init="..."] :is(button…)`
        // es un selector de atributo perfectamente valido, no marcado roto.
        $html = preg_replace('#<(style|script)\b[^>]*>.*?</\1>#si', '', $html);

        // Se comprueba el SINTOMA, no la causa. Adivinar donde acaba un
        // atributo a base de heuristicas ya fallo: con el fallo real delante,
        // `Guardado hace un momento"` pasaba por un atributo booleano y el
        // test daba verde. Lo que nunca es ambiguo es el resultado: si el
        // componente se derrama, su codigo aparece como TEXTO en la pagina.
        // Con un PARSER, no con strip_tags: strip_tags borra desde '<' hasta
        // el primer '>' sin mirar las comillas, asi que en el HTML roto se
        // comia justo el fragmento derramado (el '>' de `q =>`) y el test
        // daba verde con el desastre en pantalla. libxml respeta las comillas
        // de atributo igual que el navegador.
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        libxml_clear_errors();
        $texto = $dom->textContent;

        foreach ([
            'this.quotes', 'this.form', 'this.selected',      // estado del componente
            '=> {', 'function(', 'await fetch(',               // codigo suelto
            'X-CSRF-TOKEN',                                    // y el token, a la vista
        ] as $fuga) {
            $this->assertStringNotContainsString($fuga, $texto,
                "En {$ruta} se esta volcando el componente Alpine como texto plano: aparece "
                . "'{$fuga}' en el contenido visible. Causa casi segura: una comilla DOBLE "
                . "dentro de una expresion x-* —basta con que este en un comentario de JS—, "
                . "que cierra el atributo HTML antes de tiempo.");
        }
    }
}
