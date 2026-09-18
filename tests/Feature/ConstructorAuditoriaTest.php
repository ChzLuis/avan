<?php

namespace Tests\Feature;

use App\Modules\Tienda\Storefront\ConstructorAuditor;
use Tests\TestCase;

/**
 * Invariantes de la reorganización: cada configuración con un único propietario
 * y un único lugar donde se modifica.
 *
 * Estos límites se cruzaron varias veces a mano durante la reorganización y
 * cada vez se descubrió tarde. A partir de aquí lo caza la batería: si alguien
 * añade un control duplicado, un ajuste sin editor o un control que escribe al
 * vacío, este test se pone rojo.
 */
class ConstructorAuditoriaTest extends TestCase
{
    private array $auditoria;

    protected function setUp(): void
    {
        parent::setUp();
        $this->auditoria = ConstructorAuditor::make()->auditar();
    }

    private function detalle(string $tipo): string
    {
        $casos = $this->auditoria['detalle'][$tipo] ?? [];
        $lineas = [];
        foreach ($casos as $clave => $donde) {
            $lineas[] = is_int($clave)
                ? '  · '.$donde
                : '  · '.$clave.' → '.implode(', ', (array) $donde);
        }

        return $lineas === [] ? '' : "\n".implode("\n", array_slice($lineas, 0, 12));
    }

    /** Dos interfaces no pueden editar la misma clave. */
    public function test_ninguna_clave_tiene_dos_editores(): void
    {
        $this->assertSame(0, $this->auditoria['DUPLICATE_EDITORS'],
            'Hay claves con más de un editor activo:'.$this->detalle('duplicados'));
    }

    /** Si el código lo consume, el comerciante debe poder cambiarlo. */
    public function test_ningun_ajuste_vivo_se_queda_sin_editor(): void
    {
        $this->assertSame(0, $this->auditoria['LIVE_SETTINGS_WITHOUT_EDITOR'],
            'Ajustes que se consumen y nadie puede editar:'.$this->detalle('huerfanos'));
    }

    /** Un control que no cambia nada engaña al comerciante. */
    public function test_ningun_control_escribe_al_vacio(): void
    {
        $this->assertSame(0, $this->auditoria['INERT_CONTROLS'],
            'Controles sin consumidor (deprecarlos si es a propósito):'.$this->detalle('inertes'));
    }

    /** Los datos maestros solo se escriben desde 01 Datos del negocio. */
    public function test_los_datos_maestros_solo_se_editan_en_la_etapa_01(): void
    {
        $this->assertSame(0, $this->auditoria['MASTER_DATA_WRITE_VIOLATIONS'],
            'Datos maestros editados fuera de 01:'.$this->detalle('violaciones'));
    }

    /** Toda configuración pertenece a una de las 9 etapas. */
    public function test_toda_configuracion_tiene_etapa_propietaria(): void
    {
        $this->assertSame(0, $this->auditoria['UNCLASSIFIED'],
            'Claves sin etapa propietaria:'.$this->detalle('sin_clasificar'));
    }

    /** El inventario no puede quedarse vacío por un fallo del barrido. */
    public function test_la_auditoria_examina_el_constructor_completo(): void
    {
        $this->assertGreaterThan(150, count($this->auditoria['claves']),
            'El barrido encontró muy pocas claves: probablemente falló al leer las etapas.');
    }
}
