<?php

namespace Tests\Feature;

use App\Jobs\GenerarImagenesProducto;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductImageTemplate;
use App\Models\Project;
use App\Models\User;
use App\Support\Imagen\CompositorProducto;
use App\Support\Imagen\GeneradorImagenProducto;
use App\Support\Imagen\ResolutorImagenProducto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Plantilla automática de imágenes de producto.
 *
 * Cubre el contrato que no se puede romper: el original nunca se toca, apagar
 * la plantilla devuelve el catálogo a las fotos de siempre, un fallo no deja
 * al producto sin imagen y ninguna tienda alcanza el material de otra.
 */
class ProductImageTemplateTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;

    private Product $product;

    private ProductImage $image;

    protected function setUp(): void
    {
        parent::setUp();
        ResolutorImagenProducto::olvidar();

        $this->project = $this->crearTienda('Tienda A', 'tienda-a');
        [$this->product, $this->image] = $this->crearProductoConFoto($this->project, 'Lámpara');
    }

    protected function tearDown(): void
    {
        foreach (glob(public_path('uploads/qa-test/*.png')) as $f) {
            @unlink($f);
        }
        foreach (glob(public_path('uploads/generadas/*/*/*')) as $f) {
            @unlink($f);
        }
        ResolutorImagenProducto::olvidar();
        parent::tearDown();
    }

    private function crearTienda(string $nombre, string $slug): Project
    {
        return Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => $nombre, 'slug' => $slug, 'is_active' => true,
        ]);
    }

    /** Escribe un PNG real en disco: el compositor lee píxeles, no rutas falsas. */
    private function crearProductoConFoto(Project $project, string $nombre, string $color = 'rojo'): array
    {
        $dir = public_path('uploads/qa-test');
        @mkdir($dir, 0777, true);
        $archivo = 'qa-'.$project->id.'-'.uniqid().'.png';

        $im = imagecreatetruecolor(200, 200);
        $rgb = $color === 'rojo' ? [220, 30, 30] : [30, 30, 220];
        imagefilledrectangle($im, 0, 0, 200, 200, imagecolorallocate($im, ...$rgb));
        imagepng($im, $dir.'/'.$archivo);
        imagedestroy($im);

        $product = $project->products()->create(['name' => $nombre, 'price' => 10, 'is_available' => true]);
        $image = $product->images()->create([
            'url' => 'uploads/qa-test/'.$archivo, 'is_main' => true, 'sort_order' => 0,
        ]);

        return [$product, $image];
    }

    private function plantillaPara(Project $project, array $config = [], bool $encendida = true): ProductImageTemplate
    {
        $t = ProductImageTemplate::create([
            'project_id' => $project->id, 'name' => 'QA',
            'is_active' => true, 'enabled' => $encendida,
            'config' => array_merge(ProductImageTemplate::DEFAULTS, [
                'logo_enabled' => false, 'watermark_enabled' => false, 'output_width' => 600,
            ], $config),
        ]);
        $t->forceFill(['hash' => $t->calcularHash()])->save();

        return $t;
    }

    // ── A / B / C: qué imagen sirve la tienda ──────────────────────────────

    public function test_a_con_la_plantilla_apagada_la_tienda_usa_la_foto_original(): void
    {
        $t = $this->plantillaPara($this->project, [], encendida: false);
        app(GeneradorImagenProducto::class)->generar($this->image->fresh(), $t);
        ResolutorImagenProducto::olvidar();

        $this->assertStringNotContainsString('generadas', $this->product->fresh()->main_image_url);
    }

    public function test_b_con_la_plantilla_encendida_la_tienda_usa_la_version_generada(): void
    {
        $t = $this->plantillaPara($this->project);
        $this->assertTrue(app(GeneradorImagenProducto::class)->generar($this->image->fresh(), $t));
        ResolutorImagenProducto::olvidar();

        $this->assertStringContainsString('generadas', $this->product->fresh()->main_image_url);
    }

    public function test_c_sin_version_generada_se_cae_a_la_original(): void
    {
        $this->plantillaPara($this->project);
        ResolutorImagenProducto::olvidar();

        // Nunca se generó nada: la tienda no puede quedarse sin imagen.
        $this->assertStringContainsString('qa-test', $this->product->fresh()->main_image_url);
    }

    // ── D / L: el original es intocable ───────────────────────────────────

    public function test_d_generar_no_sobrescribe_la_imagen_original(): void
    {
        $urlOriginal = $this->image->url;
        $rutaOriginal = app(CompositorProducto::class)->rutaLocal($urlOriginal);
        $bytesAntes = file_get_contents($rutaOriginal);

        $t = $this->plantillaPara($this->project);
        app(GeneradorImagenProducto::class)->generar($this->image->fresh(), $t);

        $this->assertSame($urlOriginal, $this->image->fresh()->url);
        $this->assertSame($bytesAntes, file_get_contents($rutaOriginal), 'El archivo original cambió.');
    }

    public function test_l_regenerar_parte_siempre_del_original_no_de_la_version_anterior(): void
    {
        $gen = app(GeneradorImagenProducto::class);
        $t = $this->plantillaPara($this->project, ['product_scale' => 90]);
        $gen->generar($this->image->fresh(), $t);
        $primera = $this->image->fresh()->generated_url;

        // Se cambia la plantilla y se regenera: el resultado debe salir de la
        // foto original. Si partiera de la anterior, el producto encogería.
        $cfg = $t->configCompleta();
        $cfg['product_scale'] = 40;
        $t->forceFill(['config' => $cfg])->save();
        $gen->generar($this->image->fresh(), $t, true);

        $img = $this->image->fresh();
        $this->assertNotSame($primera, $img->generated_url, 'La huella debe cambiar el archivo.');
        $this->assertSame($t->calcularHash(), $img->generated_hash);
        // Y el original sigue siendo la fuente.
        $this->assertNotNull(app(CompositorProducto::class)->rutaLocal($img->url));
    }

    // ── E / M: configurar y regenerar ─────────────────────────────────────

    public function test_e_y_m_la_configuracion_se_guarda_y_permite_regenerar(): void
    {
        $user = User::factory()->create(['is_superadmin' => true]);
        $this->project->update(['owner_id' => $user->id]);

        $config = array_merge(ProductImageTemplate::DEFAULTS, [
            'background_type' => 'color', 'background_color' => '#123456',
            'product_scale' => 55, 'logo_x' => 20, 'logo_y' => 80,
            'logo_opacity' => 33, 'watermark_enabled' => true, 'watermark_opacity' => 7,
            'aspect_ratio' => '4:5',
        ]);

        $this->actingAs($user)->withSession(['active_project_id' => $this->project->id])
            ->postJson(route('builder.image-template.save'), ['config' => $config, 'enabled' => true])
            ->assertOk()->assertJsonPath('ok', true);

        $t = ProductImageTemplate::allProjects()->where('project_id', $this->project->id)->first();
        $c = $t->configCompleta();

        $this->assertSame(55, (int) $c['product_scale']);
        $this->assertSame(20, (int) $c['logo_x']);
        $this->assertSame(80, (int) $c['logo_y']);
        $this->assertSame(33, (int) $c['logo_opacity']);
        $this->assertSame(7, (int) $c['watermark_opacity']);
        $this->assertSame('#123456', $c['background_color']);
        $this->assertTrue($t->enabled);
        // 4:5 sobre 1200 de ancho -> 1500 de alto.
        $this->assertSame(1500, $t->alto());
    }

    // ── F / G / H: aislamiento entre empresas ─────────────────────────────

    public function test_f_y_g_una_tienda_no_alcanza_la_plantilla_ni_el_logo_de_otra(): void
    {
        $otra = $this->crearTienda('Tienda B', 'tienda-b');
        $this->plantillaPara($otra, ['logo_enabled' => true]);

        $user = User::factory()->create(['is_superadmin' => true]);
        $this->project->update(['owner_id' => $user->id]);

        // Al abrir el generador desde la tienda A se crea SU plantilla; la de B
        // no se ve ni se toca.
        $r = $this->actingAs($user)->withSession(['active_project_id' => $this->project->id])
            ->getJson(route('builder.image-template.show'))->assertOk();

        $this->assertSame($this->project->id,
            ProductImageTemplate::allProjects()->find($r->json('template.id'))->project_id);

        foreach ($r->json('templates') as $fila) {
            $this->assertSame($this->project->id,
                ProductImageTemplate::allProjects()->find($fila['id'])->project_id);
        }
        $this->assertCount(1, $r->json('templates'), 'Se listó una plantilla ajena.');
    }

    public function test_h_el_procesamiento_masivo_respeta_la_tienda(): void
    {
        $otra = $this->crearTienda('Tienda B', 'tienda-b');
        [, $imagenAjena] = $this->crearProductoConFoto($otra, 'Ajeno', 'azul');

        $t = $this->plantillaPara($this->project);

        // Aunque llegue un id ajeno en la carga del trabajo, no se procesa.
        (new GenerarImagenesProducto($t->id, [$this->image->id, $imagenAjena->id], true))
            ->handle(app(GeneradorImagenProducto::class));

        $this->assertNotNull($this->image->fresh()->generated_url);
        $this->assertNull($imagenAjena->fresh()->generated_url, 'Se procesó una imagen de otra tienda.');
    }

    public function test_n_aplicar_a_una_categoria_no_alcanza_a_las_demas(): void
    {
        $user = User::factory()->create(['is_superadmin' => true]);
        $this->project->update(['owner_id' => $user->id]);
        $this->plantillaPara($this->project);

        $cat = $this->project->categories()->create(['name' => 'Luces', 'is_active' => true]);
        [$dentro, $imgDentro] = $this->crearProductoConFoto($this->project, 'Dentro');
        $dentro->update(['category_id' => $cat->id]);

        $this->actingAs($user)->withSession(['active_project_id' => $this->project->id])
            ->postJson(route('builder.image-template.apply'), ['scope' => 'category', 'category_id' => $cat->id, 'force' => true])
            ->assertOk();

        $this->assertNotNull($imgDentro->fresh()->generated_url);
        $this->assertNull($this->image->fresh()->generated_url, 'Se regeneró un producto fuera de la categoría.');
    }

    public function test_o_aplicar_a_seleccionados_solo_toca_los_elegidos(): void
    {
        $user = User::factory()->create(['is_superadmin' => true]);
        $this->project->update(['owner_id' => $user->id]);
        $this->plantillaPara($this->project);

        [$otroProducto, $otraImagen] = $this->crearProductoConFoto($this->project, 'No elegido');

        $this->actingAs($user)->withSession(['active_project_id' => $this->project->id])
            ->postJson(route('builder.image-template.apply'), ['scope' => 'selected', 'product_ids' => [$this->product->id], 'force' => true])
            ->assertOk();

        $this->assertNotNull($this->image->fresh()->generated_url);
        $this->assertNull($otraImagen->fresh()->generated_url);
    }

    // ── I / K: fallos que no deben romper el catálogo ─────────────────────

    public function test_i_y_k_un_archivo_ilegible_no_deja_al_producto_sin_imagen(): void
    {
        $t = $this->plantillaPara($this->project);
        $this->image->forceFill(['url' => 'uploads/qa-test/no-existe.png'])->save();

        $this->assertFalse(app(GeneradorImagenProducto::class)->generar($this->image->fresh(), $t));

        $img = $this->image->fresh();
        $this->assertSame('error', $img->generation_status);
        $this->assertNull($img->generated_url);

        ResolutorImagenProducto::olvidar();
        // El catálogo sigue devolviendo algo: nunca un producto sin imagen.
        $this->assertNotNull($this->product->fresh()->main_image_url);
    }

    public function test_i_rechaza_una_ruta_fuera_de_las_carpetas_permitidas(): void
    {
        $compositor = app(CompositorProducto::class);

        $this->assertNull($compositor->rutaLocal('../../../.env'));
        $this->assertNull($compositor->rutaLocal('uploads/../../.env'));
        $this->assertNull($compositor->rutaLocal(''));
    }

    // ── J / P / Q: flujo del negocio ─────────────────────────────────────

    public function test_j_un_producto_nuevo_se_procesa_con_la_plantilla_activa(): void
    {
        $t = $this->plantillaPara($this->project);
        [, $nueva] = $this->crearProductoConFoto($this->project, 'Recién subido');

        (new GenerarImagenesProducto($t->id, [$nueva->id], false))
            ->handle(app(GeneradorImagenProducto::class));

        $this->assertNotNull($nueva->fresh()->generated_url);
        $this->assertSame('completado', $nueva->fresh()->generation_status);
    }

    public function test_p_la_galeria_solo_se_procesa_si_se_pide(): void
    {
        $secundaria = $this->product->images()->create([
            'url' => $this->image->url, 'is_main' => false, 'sort_order' => 1,
        ]);

        $gen = app(GeneradorImagenProducto::class);

        $soloPrincipal = $this->plantillaPara($this->project, ['apply_to_gallery' => false]);
        $this->assertCount(1, $gen->imagenesDe($this->product->id, $soloPrincipal));

        $conGaleria = $soloPrincipal;
        $cfg = $conGaleria->configCompleta();
        $cfg['apply_to_gallery'] = true;
        $conGaleria->forceFill(['config' => $cfg])->save();
        $this->assertCount(2, $gen->imagenesDe($this->product->id, $conGaleria));
    }

    public function test_q_apagar_la_plantilla_vuelve_al_original_sin_borrar_archivos(): void
    {
        $t = $this->plantillaPara($this->project);
        app(GeneradorImagenProducto::class)->generar($this->image->fresh(), $t);

        $generada = $this->image->fresh()->generated_url;
        $rutaGenerada = app(CompositorProducto::class)->rutaLocal($generada);
        $this->assertNotNull($rutaGenerada);

        $t->forceFill(['enabled' => false])->save();
        ResolutorImagenProducto::olvidar();

        $this->assertStringNotContainsString('generadas', $this->product->fresh()->main_image_url);
        $this->assertFileExists($rutaGenerada, 'Apagar la plantilla borró el archivo generado.');
        $this->assertSame($generada, $this->image->fresh()->generated_url);
    }

    // ── Plantillas guardadas ─────────────────────────────────────────────

    public function test_guardar_como_crea_una_plantilla_nueva_y_la_deja_activa(): void
    {
        $user = User::factory()->create(['is_superadmin' => true]);
        $this->project->update(['owner_id' => $user->id]);
        $primera = $this->plantillaPara($this->project, ['product_scale' => 70]);

        $this->actingAs($user)->withSession(['active_project_id' => $this->project->id])
            ->postJson(route('builder.image-template.save-as'), ['name' => 'Campaña Navidad'])
            ->assertOk()->assertJsonPath('template.name', 'Campaña Navidad');

        $todas = ProductImageTemplate::allProjects()->where('project_id', $this->project->id)->get();
        $this->assertCount(2, $todas, 'La anterior debe conservarse.');
        $this->assertSame(1, $todas->where('is_active', true)->count(), 'Solo puede haber una activa.');
        // Hereda la configuración actual: sirve para partir de lo ya ajustado.
        $this->assertSame(70, (int) $todas->firstWhere('name', 'Campaña Navidad')->configCompleta()['product_scale']);
        $this->assertFalse($primera->fresh()->is_active);
    }

    public function test_activar_otra_plantilla_no_regenera_ni_borra_imagenes(): void
    {
        $user = User::factory()->create(['is_superadmin' => true]);
        $this->project->update(['owner_id' => $user->id]);

        $navidad = $this->plantillaPara($this->project, ['product_scale' => 70]);
        app(GeneradorImagenProducto::class)->generar($this->image->fresh(), $navidad);
        $generada = $this->image->fresh()->generated_url;

        $otra = ProductImageTemplate::create([
            'project_id' => $this->project->id, 'name' => 'Blanco', 'is_active' => false,
            'enabled' => true, 'config' => ProductImageTemplate::DEFAULTS,
        ]);

        $this->actingAs($user)->withSession(['active_project_id' => $this->project->id])
            ->postJson(route('builder.image-template.activate'), ['id' => $otra->id])
            ->assertOk()->assertJsonPath('template.name', 'Blanco');

        $this->assertTrue($otra->fresh()->is_active);
        $this->assertFalse($navidad->fresh()->is_active);
        // Cambiar de plantilla es una decisión, no una regeneración masiva.
        $this->assertSame($generada, $this->image->fresh()->generated_url);
        $this->assertNotNull(app(CompositorProducto::class)->rutaLocal($generada));
    }

    public function test_no_se_puede_activar_la_plantilla_de_otra_tienda(): void
    {
        $user = User::factory()->create(['is_superadmin' => true]);
        $this->project->update(['owner_id' => $user->id]);
        $this->plantillaPara($this->project);

        $otraTienda = $this->crearTienda('Tienda B', 'tienda-b');
        $ajena = $this->plantillaPara($otraTienda);

        $this->actingAs($user)->withSession(['active_project_id' => $this->project->id])
            ->postJson(route('builder.image-template.activate'), ['id' => $ajena->id])
            ->assertNotFound();

        $this->assertTrue($ajena->fresh()->is_active, 'La plantilla ajena no debe alterarse.');
    }

    // ── Rendimiento: no rehacer lo que no cambió ──────────────────────────

    public function test_no_regenera_cuando_la_plantilla_no_cambio(): void
    {
        $gen = app(GeneradorImagenProducto::class);
        $t = $this->plantillaPara($this->project);

        $this->assertTrue($gen->generar($this->image->fresh(), $t));
        $this->assertFalse($gen->generar($this->image->fresh(), $t), 'Regeneró sin que cambiara nada.');
    }
}
