<?php

namespace App\Support;

/**
 * Modelo canónico de accesos — FASE 4 del plan de Perfiles y Accesos.
 *
 * FUENTE DE VERDAD ÚNICA. Áreas, niveles, nombres, descripciones y jerarquía se
 * definen aquí y en ningún otro sitio. Rutas, seeders, pantallas y pruebas leen
 * de aquí: si se repartiera por el código, volveríamos al problema que originó
 * todo esto —90 permisos inventados área por área, sin criterio común—.
 *
 * La forma es siempre la misma para las 12 áreas:
 *
 *     Sin acceso  <  Ver  <  Trabajar  <  Administrar
 *
 * Y los niveles son ACUMULATIVOS: quien tiene Administrar puede hacer lo de
 * Trabajar y lo de Ver. Eso no se guarda por triplicado en la base; se resuelve
 * al comprobar el permiso (ver `AppServiceProvider`), de modo que un rol guarda
 * UNA fila por área y es imposible acabar con «editar sin ver».
 */
class Access
{
    public const VER         = 'ver';
    public const TRABAJAR    = 'trabajar';
    public const ADMINISTRAR = 'administrar';

    /** De menor a mayor. El orden ES la jerarquía. */
    public const NIVELES = [self::VER, self::TRABAJAR, self::ADMINISTRAR];

    public const ETIQUETA_NIVEL = [
        self::VER         => 'Ver',
        self::TRABAJAR    => 'Trabajar',
        self::ADMINISTRAR => 'Administrar',
    ];

    public const AYUDA_NIVEL = [
        self::VER         => 'Puede consultar la información, pero no modificarla.',
        self::TRABAJAR    => 'Puede realizar las tareas habituales del área, como crear y editar.',
        self::ADMINISTRAR => 'Puede además eliminar, anular, configurar o ejecutar acciones masivas.',
    ];

    /**
     * Las 12 áreas. `clave` forma el permiso: `{clave}.{nivel}`.
     *
     * `agenda` y `caja` reutilizan a propósito los permisos `agenda.ver` y
     * `caja.ver` que ya existían: son exactamente el mismo concepto, así que
     * crear un duplicado sería ensuciar la tabla sin motivo.
     */
    public const AREAS = [
        'catalogo' => [
            'nombre' => 'Catálogo',
            'que_es' => 'Productos, servicios y categorías.',
            'ver'         => 'Consultar productos, servicios y categorías, y exportarlos.',
            'trabajar'    => 'Crear y editar productos, precios, imágenes e información comercial.',
            'administrar' => 'Eliminar, importar en masa y vaciar el catálogo.',
        ],
        'inventario' => [
            'nombre' => 'Inventario',
            'que_es' => 'Stock, Kardex y movimientos de mercadería.',
            'ver'         => 'Consultar el stock, el Kardex y los movimientos.',
            'trabajar'    => 'Registrar entradas, salidas y transferencias.',
            'administrar' => 'Ajustes, regularizaciones, importación y correcciones críticas.',
        ],
        'pedidos' => [
            'nombre' => 'Pedidos',
            'que_es' => 'Pedidos de clientes y su reparto.',
            'ver'         => 'Consultar pedidos y su estado.',
            'trabajar'    => 'Crear pedidos y hacerlos avanzar.',
            'administrar' => 'Anular, eliminar y ejecutar operaciones críticas.',
        ],
        'cotizaciones' => [
            'nombre' => 'Cotizaciones',
            'que_es' => 'Presupuestos enviados a clientes.',
            'ver'         => 'Consultar cotizaciones.',
            'trabajar'    => 'Crear, editar, enviar y convertir en pedido.',
            'administrar' => 'Eliminar y operaciones administrativas.',
        ],
        'clientes' => [
            'nombre' => 'Clientes',
            'que_es' => 'Datos de contacto y seguimiento comercial.',
            'ver'         => 'Consultar la ficha de los clientes.',
            'trabajar'    => 'Registrar y editar clientes.',
            'administrar' => 'Eliminar, fusionar e importar.',
        ],
        'pos' => [
            'nombre' => 'Punto de venta',
            'que_es' => 'Venta en mostrador.',
            'ver'         => 'Consultar las operaciones del mostrador.',
            'trabajar'    => 'Vender y cobrar.',
            'administrar' => 'Aplicar descuentos especiales y anular ventas.',
        ],
        'caja' => [
            'nombre' => 'Caja',
            'que_es' => 'Apertura, cierre y movimientos de efectivo.',
            'ver'         => 'Consultar el estado y el historial de caja.',
            'trabajar'    => 'Abrir, cerrar y registrar movimientos.',
            'administrar' => 'Correcciones y anulaciones.',
        ],
        'facturacion' => [
            'nombre' => 'Facturación',
            'que_es' => 'Boletas, facturas y comprobantes.',
            'ver'         => 'Consultar los comprobantes emitidos.',
            'trabajar'    => 'Emitir comprobantes.',
            'administrar' => 'Anular comprobantes y administrar la emisión.',
        ],
        'cobros' => [
            'nombre' => 'Cobros',
            'que_es' => 'Pagos recibidos y cuentas por cobrar.',
            'ver'         => 'Consultar los pagos y lo que está pendiente.',
            'trabajar'    => 'Registrar y gestionar cobros.',
            'administrar' => 'Aprobar, rechazar y revertir pagos.',
        ],
        'agenda' => [
            'nombre' => 'Agenda',
            'que_es' => 'Citas y reservas.',
            'ver'         => 'Consultar la agenda.',
            'trabajar'    => 'Crear, editar y reprogramar citas.',
            'administrar' => 'Configurar la agenda y operaciones administrativas.',
        ],
        'personal' => [
            'nombre' => 'Personal',
            'que_es' => 'Empleados, asistencia y turnos.',
            'ver'         => 'Consultar el personal y su asistencia.',
            'trabajar'    => 'Gestionar empleados, asistencia y turnos.',
            'administrar' => 'Eliminar personal y configurar el área.',
        ],
        'configuracion' => [
            'nombre' => 'Configuración',
            'que_es' => 'Ajustes del negocio y de la tienda.',
            'ver'         => 'Consultar la configuración.',
            'trabajar'    => 'Cambiar datos del negocio, diseño de la tienda, listas y QR.',
            'administrar' => 'Dominios, medios de pago, bots, integraciones y estructura del negocio.',
        ],
    ];

    /**
     * Equivalencia permiso antiguo → permiso canónico — FASE 5.
     *
     * Los 90 permisos heredados tienen decisión documentada. Los que NO aparecen
     * aquí es porque se decidió expresamente no darles equivalente:
     *
     *   reports.ver · reports.exportar   Reportes es transversal: se ve lo que ya
     *                                    puedes ver por el área del dato.
     *   rifas.*                          Módulo sin una sola fila en producción.
     *   tickets.*                        No existe ni la tabla.
     *   view-requests · manage-requests   Solicitudes: tablas vacías, y además
     *                                    nomenclatura inglesa.
     *
     * La justificación completa está en `docs/perfiles-accesos/05-CLASIFICACION-PERMISOS.md`.
     */
    public const MAPEO_LEGACY = [
        // ── Catálogo ──
        'catalog.ver'                       => 'catalogo.ver',
        'catalog.crear'                     => 'catalogo.trabajar',
        'catalog.editar'                    => 'catalogo.trabajar',
        'catalog.resenas'                   => 'catalogo.trabajar',
        'catalog.importar'                  => 'catalogo.administrar',
        'catalog.eliminar'                  => 'catalogo.administrar',
        'catalog-integrations.view'         => 'catalogo.ver',
        'catalog-integrations.view-history' => 'catalogo.ver',
        'catalog-integrations.sync'         => 'catalogo.trabajar',
        'catalog-integrations.manage'       => 'catalogo.administrar',
        'view-catalog'                      => 'catalogo.ver',
        'create-products'                   => 'catalogo.trabajar',
        'edit-products'                     => 'catalogo.trabajar',
        'delete-products'                   => 'catalogo.administrar',

        // ── Inventario (incluye proveedores: quien entra mercadería la compra) ──
        'inventory.ver'       => 'inventario.ver',
        'inventory.editar'    => 'inventario.trabajar',
        'proveedores.ver'     => 'inventario.ver',
        'proveedores.editar'  => 'inventario.trabajar',

        // ── Pedidos (incluye reparto y logística) ──
        'orders.ver'        => 'pedidos.ver',
        'orders.crear'      => 'pedidos.trabajar',
        'orders.editar'     => 'pedidos.trabajar',
        'orders.eliminar'   => 'pedidos.administrar',
        'orders.cancelar'   => 'pedidos.administrar',
        'view-orders'       => 'pedidos.ver',
        'manage-orders'     => 'pedidos.administrar',
        'view-logistics'    => 'pedidos.ver',
        'manage-logistics'  => 'pedidos.trabajar',
        'mapa.ver'          => 'pedidos.ver',
        'mapa.editar'       => 'pedidos.trabajar',

        // ── Cotizaciones ──
        'quotes.ver'      => 'cotizaciones.ver',
        'quotes.crear'    => 'cotizaciones.trabajar',
        'quotes.editar'   => 'cotizaciones.trabajar',
        'quotes.eliminar' => 'cotizaciones.administrar',
        'view-quotes'     => 'cotizaciones.ver',
        'manage-quotes'   => 'cotizaciones.administrar',

        // ── Clientes ──
        'clients.ver'      => 'clientes.ver',
        'clients.crear'    => 'clientes.trabajar',
        'clients.editar'   => 'clientes.trabajar',
        'clients.eliminar' => 'clientes.administrar',
        'view-clients'     => 'clientes.ver',
        'manage-clients'   => 'clientes.administrar',

        // ── Punto de venta. El descuento es potestad del mostrador, no del pedido. ──
        'pos.usar'         => 'pos.trabajar',
        'orders.descuento' => 'pos.administrar',

        // ── Caja ──
        'caja.ver'        => 'caja.ver',
        'caja.abrir'      => 'caja.trabajar',
        'caja.cerrar'     => 'caja.trabajar',
        'caja.movimiento' => 'caja.trabajar',

        // ── Facturación ──
        'invoices.ver'    => 'facturacion.ver',
        'invoices.crear'  => 'facturacion.trabajar',
        'invoices.editar' => 'facturacion.trabajar',
        'invoices.anular' => 'facturacion.administrar',

        // ── Cobros ──
        'payments.ver'      => 'cobros.ver',
        'payments.aprobar'  => 'cobros.administrar',
        'payments.rechazar' => 'cobros.administrar',

        // ── Agenda ──
        'agenda.ver'      => 'agenda.ver',
        'agenda.crear'    => 'agenda.trabajar',
        'agenda.editar'   => 'agenda.trabajar',
        'agenda.eliminar' => 'agenda.administrar',
        'view-agenda'     => 'agenda.ver',
        'manage-agenda'   => 'agenda.administrar',

        // ── Personal ──
        'hr.ver'            => 'personal.ver',
        'hr.crear'          => 'personal.trabajar',
        'hr.editar'         => 'personal.trabajar',
        'hr.eliminar'       => 'personal.administrar',
        'attendance.ver'    => 'personal.ver',
        'attendance.fichar' => 'personal.trabajar',
        'attendance.editar' => 'personal.trabajar',
        'view-hr'           => 'personal.ver',
        'manage-hr'         => 'personal.administrar',

        // ── Configuración. Pagos sube a Administrar: toca dinero. ──
        'settings.ver'       => 'configuracion.ver',
        'settings.negocio'   => 'configuracion.trabajar',
        'settings.diseno'    => 'configuracion.trabajar',
        'settings.catalogos' => 'configuracion.trabajar',
        'settings.qr'        => 'configuracion.trabajar',
        'settings.pagos'     => 'configuracion.administrar',
        'settings.editar'    => 'configuracion.administrar',
        'manage-settings'    => 'configuracion.administrar',
        'manage-modules'     => 'configuracion.administrar',
        'manage-members'     => 'configuracion.administrar',
        'roles.ver'          => 'configuracion.ver',
        'roles.gestionar'    => 'configuracion.administrar',
    ];

    /**
     * Traduce una lista de permisos antiguos a canónicos, quedándose con el
     * nivel MÁS ALTO de cada área. Quien tenía `catalog.ver` y `catalog.eliminar`
     * acaba con `catalogo.administrar` a secas: la herencia le devuelve los otros
     * dos y el perfil guarda una sola fila por área.
     */
    public static function traducir(array $permisosAntiguos): array
    {
        $porArea = [];

        foreach ($permisosAntiguos as $antiguo) {
            $nuevo = self::MAPEO_LEGACY[$antiguo] ?? null;
            if (!$nuevo) {
                continue;
            }

            [$area, $nivel] = self::partes($nuevo);
            $actual = $porArea[$area] ?? null;

            if ($actual === null
                || array_search($nivel, self::NIVELES, true) > array_search($actual, self::NIVELES, true)) {
                $porArea[$area] = $nivel;
            }
        }

        $resultado = [];
        foreach ($porArea as $area => $nivel) {
            $resultado[] = "{$area}.{$nivel}";
        }
        sort($resultado);

        return $resultado;
    }

    /** Permisos antiguos que se decidió NO traducir. */
    public static function sinEquivalente(array $todosLosAntiguos): array
    {
        return array_values(array_filter(
            $todosLosAntiguos,
            fn ($p) => !isset(self::MAPEO_LEGACY[$p]) && !self::partes($p)
        ));
    }

    /** Los 36 permisos canónicos, en orden. */
    public static function permisos(): array
    {
        $lista = [];
        foreach (array_keys(self::AREAS) as $area) {
            foreach (self::NIVELES as $nivel) {
                $lista[] = "{$area}.{$nivel}";
            }
        }

        return $lista;
    }

    /** `catalogo.trabajar` → ['catalogo', 'trabajar']. Null si no es canónico. */
    public static function partes(string $permiso): ?array
    {
        if (!str_contains($permiso, '.')) {
            return null;
        }

        [$area, $nivel] = explode('.', $permiso, 2);

        return isset(self::AREAS[$area]) && in_array($nivel, self::NIVELES, true)
            ? [$area, $nivel]
            : null;
    }

    /**
     * Niveles que CONCEDEN el permiso pedido, por la regla de herencia.
     * Para `catalogo.ver` devuelve catalogo.trabajar y catalogo.administrar.
     */
    public static function equivalentesSuperiores(string $permiso): array
    {
        $partes = self::partes($permiso);
        if (!$partes) {
            return [];
        }

        [$area, $nivel] = $partes;
        $desde = array_search($nivel, self::NIVELES, true);

        return array_map(
            fn ($n) => "{$area}.{$n}",
            array_slice(self::NIVELES, $desde + 1)
        );
    }

    /** Descripción de un permiso concreto, para la ayuda contextual. */
    public static function descripcion(string $permiso): ?string
    {
        $partes = self::partes($permiso);

        return $partes ? (self::AREAS[$partes[0]][$partes[1]] ?? null) : null;
    }

    public static function etiqueta(string $permiso): ?string
    {
        $partes = self::partes($permiso);

        return $partes
            ? self::AREAS[$partes[0]]['nombre'].' / '.self::ETIQUETA_NIVEL[$partes[1]]
            : null;
    }
}
