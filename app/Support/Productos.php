<?php

namespace App\Support;

use App\Models\Module;
use App\Models\Project;

/**
 * Los PRODUCTOS comerciales de BIXO como agrupaciones de módulos.
 *
 * Decisión de arquitectura (BIXO_MODULARIZACION_PLAN, MODULE_OWNERSHIP): los
 * productos (CRM, Sales, Commerce, Operations) no son carpetas ni sistemas
 * aparte; son conjuntos de módulos contratables sobre la misma plataforma.
 * Un negocio puede tener uno o varios. Los registros de `modules` siguen
 * siendo capacidades; esta es la capa de agrupación que faltaba para no
 * activarlas una a una desde Control y para que un negocio pueda nacer
 * "solo CRM".
 *
 * Aquí NO hay precios ni cobro: eso es facturación de la plataforma, que
 * llegará aparte. Aquí solo se define qué enciende cada producto y por qué
 * puerta entra el cliente.
 */
final class Productos
{
    public const DEFINICIONES = [
        'crm' => [
            'nombre'      => 'BIXO CRM',
            'descripcion' => 'Bandeja de WhatsApp, clientes y leads, bots con IA.',
            'modulos'     => ['clients', 'bots'],
            'portal'      => 'bixocrm.login',
        ],
        'sales' => [
            'nombre'      => 'BIXO Sales',
            'descripcion' => 'Clientes, cotizaciones, pedidos, POS, comprobantes.',
            'modulos'     => ['clients', 'catalog', 'quotes', 'orders', 'invoices'],
            'portal'      => 'bixosales.login',
        ],
        'commerce' => [
            'nombre'      => 'BIXO Commerce',
            'descripcion' => 'Tienda online con constructor, catálogo público, cupones y promociones.',
            'modulos'     => ['store', 'pages', 'catalog', 'orders', 'clients'],
            'portal'      => 'login',
        ],
        'operations' => [
            'nombre'      => 'BIXO Operations',
            'descripcion' => 'Agenda, despacho, sedes, personal y asistencia.',
            'modulos'     => ['orders', 'agenda', 'logistics', 'sedes', 'hr', 'attendance'],
            'portal'      => 'bixosales.login',
        ],
    ];

    /** Nombres para crear el módulo si la instalación no lo tiene sembrado. */
    private const NOMBRES = [
        'clients' => 'Base de clientes', 'bots' => 'Bots de WhatsApp', 'catalog' => 'Productos y categorías',
        'quotes' => 'Cotizaciones', 'orders' => 'Panel de operaciones', 'invoices' => 'Facturas y boletas',
        'store' => 'Tienda online', 'pages' => 'Páginas', 'agenda' => 'Agenda y citas', 'logistics' => 'Despacho y envíos',
        'sedes' => 'Sedes', 'hr' => 'Personal', 'attendance' => 'Asistencia',
    ];

    public static function existe(string $clave): bool
    {
        return isset(self::DEFINICIONES[$clave]);
    }

    /** @return array<string, array{nombre:string,descripcion:string,modulos:string[],portal:string}> */
    public static function todos(): array
    {
        return self::DEFINICIONES;
    }

    /** @return string[] */
    public static function modulos(string $clave): array
    {
        return self::DEFINICIONES[$clave]['modulos'] ?? [];
    }

    /**
     * Enciende en el negocio todos los módulos del producto. Solo suma: un
     * producto nunca apaga lo que otro producto ya encendió.
     */
    public static function activar(Project $project, string $clave): void
    {
        if (! self::existe($clave)) {
            throw new \InvalidArgumentException("Producto desconocido: {$clave}");
        }
        foreach (self::modulos($clave) as $key) {
            $modulo = Module::firstOrCreate(['key' => $key], [
                'name'      => self::NOMBRES[$key] ?? $key,
                'is_active' => true,
            ]);
            $project->modules()->syncWithoutDetaching([$modulo->id => ['is_active' => true]]);
        }
        $project->unsetRelation('modules');
    }

    /** Un producto está contratado cuando TODOS sus módulos están activos. */
    public static function contratado(Project $project, string $clave): bool
    {
        $activos = $project->activeModules()->pluck('key')->all();

        return self::existe($clave)
            && array_diff(self::modulos($clave), $activos) === [];
    }

    /** @return string[] claves de los productos que el negocio tiene completos */
    public static function contratados(Project $project): array
    {
        return array_values(array_filter(
            array_keys(self::DEFINICIONES),
            fn (string $clave) => self::contratado($project, $clave)
        ));
    }
}
