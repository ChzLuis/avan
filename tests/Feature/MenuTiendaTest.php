<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Modules\Tienda\Models\StoreMenu;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El menú de la tienda, de punta a punta.
 *
 * El editor vive en 02 Apariencia → «Encabezado y navegación» y guarda por seis
 * rutas propias. Lo que no había era una prueba que uniera las dos orillas: que
 * lo guardado desde el Constructor SALGA de verdad en la tienda, y que quien
 * solo puede mirar no pueda tocarlo.
 *
 * Es el mismo agujero que tenía la analítica: el ajuste se guardaba y nadie
 * comprobaba que llegara.
 */
class MenuTiendaTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;

    private User $dueno;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dueno = User::factory()->create();
        $this->project = Project::create([
            'owner_id' => $this->dueno->id,
            'name' => 'Tienda Menú', 'slug' => 'menu-'.uniqid(), 'is_active' => true,
        ]);

        /* Se prueba sobre `computienda` porque es la plantilla que SÍ pinta el
           menú, y la que usan 6 de las 8 tiendas vivas. Ojo: de las 16
           plantillas solo esta y la estructura v2 lo pintan — el resto deja el
           menú configurado y sin efecto. Está anotado en
           `test_hay_plantillas_que_no_pintan_el_menu`. */
        $this->project->settings()->create(['key' => 'catalog_template', 'value' => 'computienda']);
    }

    private function menu(): StoreMenu
    {
        return StoreMenu::firstOrCreate(
            ['project_id' => $this->project->id, 'location' => 'primary'],
            ['name' => 'Principal', 'is_active' => true]
        );
    }

    /**
     * El editor tiene que ser alcanzable: si no, el control no existe.
     *
     * Estuvo escondido dos veces seguidas. Primero como segunda pestaña de
     * Apariencia, que abría por defecto en "Plantilla y marca" y dejaba el menú
     * detrás de un clic que nadie daba. Y aunque el marcado de esa pestaña
     * llegó a producción, sus estilos NO: los botones se pintaban sin fondo ni
     * borde, invisibles. Ahora es un paso propio y numerado de la lista.
     */
    public function test_el_menu_es_un_paso_propio_del_constructor(): void
    {
        $indice = file_get_contents(app_path('Modules/Tienda/Views/settings/builder/index.blade.php'));
        $encabezado = file_get_contents(app_path('Modules/Tienda/Views/settings/builder/stages/header.blade.php'));

        $this->assertStringContainsString('store-navigation-builder', $encabezado,
            'El editor de menú no está en el paso de encabezado.');
        $this->assertStringContainsString("stage==='header'", $indice,
            'El paso del menú no se puede abrir.');

        // Se llama "menú" porque es la palabra que busca el negocio: con
        // "navegación" el editor estaba ahí y nadie lo encontraba.
        $this->assertSame('Encabezado y menú',
            \App\Modules\Tienda\Storefront\BuilderRuleRegistry::STAGES['header']['label'],
            'El paso debe nombrar el menú para que se encuentre.');

        // Con x-if se destruye el DOM al cambiar de paso y el gestor de menú
        // pierde sus listeners: tiene que ser x-show.
        $this->assertStringContainsString('<div x-show="stage===\'header\'"', $indice,
            'El paso del menú debe usar x-show para no perder los listeners.');
    }

    /** Las seis rutas del menú existen y exigen permiso de escritura. */
    public function test_las_rutas_exigen_permiso_de_escritura(): void
    {
        $rutas = ['settings.storefront.header', 'settings.storefront.publish',
            'settings.storefront.menu.items.store', 'settings.storefront.menu.items.update',
            'settings.storefront.menu.items.destroy', 'settings.storefront.menu.reorder'];

        foreach ($rutas as $nombre) {
            $ruta = collect(app('router')->getRoutes())->first(fn ($r) => $r->getName() === $nombre);
            $this->assertNotNull($ruta, "Falta la ruta {$nombre}.");
            $this->assertContains('can:settings.diseno', $ruta->gatherMiddleware(),
                "La ruta {$nombre} no exige permiso de escritura.");
        }
    }

    /** Lo que se guarda en el Constructor sale en la tienda. */
    public function test_lo_guardado_aparece_en_la_tienda(): void
    {
        $menu = $this->menu();
        $menu->items()->create([
            'project_id' => $this->project->id,
            'label' => 'OFERTAS DE TEMPORADA',
            'destination_type' => 'url', 'url' => '/ofertas',
            'sort_order' => 1, 'is_enabled' => true,
            'show_desktop' => true, 'show_tablet' => true, 'show_mobile' => true,
        ]);

        $html = $this->get(route('public.catalog', $this->project->slug))->assertOk()->getContent();

        $this->assertStringContainsString('OFERTAS DE TEMPORADA', $html,
            'El menú se guarda pero la tienda no lo pinta.');
    }

    /** Un elemento apagado no se muestra: el interruptor tiene que servir. */
    public function test_un_elemento_apagado_no_sale(): void
    {
        $menu = $this->menu();
        $menu->items()->create([
            'project_id' => $this->project->id,
            'label' => 'ENLACE OCULTO', 'destination_type' => 'url', 'url' => '/oculto',
            'sort_order' => 1, 'is_enabled' => false,
            'show_desktop' => true, 'show_tablet' => true, 'show_mobile' => true,
        ]);

        $html = $this->get(route('public.catalog', $this->project->slug))->assertOk()->getContent();

        $this->assertStringNotContainsString('ENLACE OCULTO', $html);
    }

    /** El orden que se define es el orden que se ve. */
    public function test_se_respeta_el_orden(): void
    {
        $menu = $this->menu();
        foreach ([['SEGUNDO', 2], ['PRIMERO', 1], ['TERCERO', 3]] as [$label, $orden]) {
            $menu->items()->create([
                'project_id' => $this->project->id,
                'label' => $label, 'destination_type' => 'url', 'url' => '/'.strtolower($label),
                'sort_order' => $orden, 'is_enabled' => true,
                'show_desktop' => true, 'show_tablet' => true, 'show_mobile' => true,
            ]);
        }

        $html = $this->get(route('public.catalog', $this->project->slug))->assertOk()->getContent();

        $posiciones = array_map(fn ($t) => strpos($html, $t), ['PRIMERO', 'SEGUNDO', 'TERCERO']);
        $this->assertNotContains(false, $posiciones, 'Falta algún elemento del menú.');
        $ordenado = $posiciones;
        sort($ordenado);
        $this->assertSame($ordenado, $posiciones, 'El menú no respeta el orden configurado.');
    }

    /** Cada negocio ve su menú: el del vecino no se cuela. */
    public function test_el_menu_no_se_cruza_entre_negocios(): void
    {
        $this->menu()->items()->create([
            'project_id' => $this->project->id,
            'label' => 'SOLO MIO', 'destination_type' => 'url', 'url' => '/mio',
            'sort_order' => 1, 'is_enabled' => true,
            'show_desktop' => true, 'show_tablet' => true, 'show_mobile' => true,
        ]);

        $otro = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Otra Tienda', 'slug' => 'otra-'.uniqid(), 'is_active' => true,
        ]);

        $html = $this->get(route('public.catalog', $otro->slug))->assertOk()->getContent();

        $this->assertStringNotContainsString('SOLO MIO', $html);
    }

    /**
     * Toda plantilla con cabecera pinta el menú.
     *
     * Antes solo lo hacían dos de dieciséis: en las demás el negocio
     * configuraba su menú y no salía nada, y al cambiar de plantilla desde el
     * Constructor el menú desaparecía sin avisar. Este test es el que impide
     * que vuelva a pasar cuando alguien añada una plantilla nueva.
     */
    public function test_todas_las_plantillas_pintan_el_menu(): void
    {
        // `direct` es la única sin `</header>`: su cabecera se arma distinta y
        // se trata aparte. Se nombra aquí para que la excepción sea explícita
        // y no un olvido silencioso.
        $sinCabecera = ['direct'];
        $faltan = [];

        foreach (glob(app_path('Modules/Tienda/Views/public/templates/*.blade.php')) as $ruta) {
            $nombre = basename($ruta, '.blade.php');
            if (in_array($nombre, $sinCabecera, true)) {
                continue;
            }
            $cuerpo = file_get_contents($ruta);
            $pinta = str_contains($cuerpo, 'x-store-menu')
                || str_contains($cuerpo, 'rootItems');
            if (! $pinta) {
                $faltan[] = $nombre;
            }
        }

        $this->assertSame([], $faltan,
            'Estas plantillas dejan el menú configurado y sin efecto: '.implode(', ', $faltan));
    }

    /** Y se pinta de verdad en una plantilla que antes no lo hacía. */
    public function test_el_menu_sale_en_una_plantilla_que_antes_no_lo_pintaba(): void
    {
        $this->project->settings()->where('key', 'catalog_template')->update(['value' => 'nordic']);

        $this->menu()->items()->create([
            'project_id' => $this->project->id,
            'label' => 'MENU EN NORDIC', 'destination_type' => 'url', 'url' => '/x',
            'sort_order' => 1, 'is_enabled' => true,
            'show_desktop' => true, 'show_tablet' => true, 'show_mobile' => true,
        ]);

        $html = $this->get(route('public.catalog', $this->project->slug))->assertOk()->getContent();

        $this->assertStringContainsString('MENU EN NORDIC', $html);
    }
}
