<?php

namespace App\Support;

use App\Models\Project;
use App\Models\User;

/**
 * Búsqueda global del panel comercial.
 *
 * El buscador de la barra superior no buscaba: escribir y pulsar Enter
 * llevaba a Pedidos con un `?q=`, así que un cliente, una cotización o un
 * producto eran inalcanzables desde ahí. Ahora consulta las entidades que el
 * negocio tiene de verdad y devuelve resultados listos para abrir.
 *
 * Dos reglas que no se negocian:
 *
 * 1. TODO se filtra por proyecto. Una consulta global sin ese filtro es la
 *    forma más fácil de enseñarle a un negocio los clientes de otro.
 * 2. Cada grupo respeta el módulo y el permiso de su sección. Si alguien no
 *    puede entrar a Cotizaciones, tampoco puede encontrarlas por aquí: el
 *    buscador no es una puerta trasera.
 */
class BusquedaGlobal
{
    /** Tope por grupo: el desplegable muestra un adelanto, no un listado. */
    private const POR_GRUPO = 5;

    public static function buscar(?User $user, Project $project, string $termino): array
    {
        $q = trim($termino);
        if (mb_strlen($q) < 2) {
            return [];
        }

        $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $q) . '%';

        return array_values(array_filter([
            self::clientes($user, $project, $like),
            self::cotizaciones($user, $project, $q, $like),
            self::pedidos($user, $project, $q, $like),
            self::productos($user, $project, $like),
            self::proveedores($user, $project, $like),
        ]));
    }

    /** ¿Puede el usuario ver esta sección? Mismo par A|B que las rutas. */
    private static function puede(?User $user, array $permisos): bool
    {
        if (! $user) {
            return false;
        }
        foreach ($permisos as $permiso) {
            if ($user->can($permiso)) {
                return true;
            }
        }

        return false;
    }

    private static function grupo(string $clave, string $titulo, array $items): ?array
    {
        return $items ? ['clave' => $clave, 'titulo' => $titulo, 'items' => $items] : null;
    }

    private static function clientes(?User $user, Project $project, string $like): ?array
    {
        if (! self::puede($user, ['clients.ver', 'view-clients', 'manage-clients'])) {
            return null;
        }

        $filas = $project->clients()
            ->where(fn ($w) => $w->where('name', 'like', $like)
                ->orWhere('phone', 'like', $like)
                ->orWhere('email', 'like', $like))
            ->orderBy('name')
            ->limit(self::POR_GRUPO)
            ->get(['id', 'name', 'phone', 'email']);

        return self::grupo('clientes', 'Clientes', $filas->map(fn ($c) => [
            'titulo'   => $c->name,
            'detalle'  => $c->phone ?: $c->email ?: '',
            'url'      => route('bixosales.clientes').'?q='.urlencode($c->name),
        ])->all());
    }

    private static function cotizaciones(?User $user, Project $project, string $q, string $like): ?array
    {
        if (! $project->hasModule('quotes') || ! self::puede($user, ['quotes.ver', 'view-quotes'])) {
            return null;
        }

        $filas = $project->quotes()
            ->where(fn ($w) => $w->where('client_name', 'like', $like)
                ->orWhere('numero', 'like', $like)
                // Escribir '33' tiene que encontrar la COT-00033.
                ->orWhere('id', ltrim($q, '0') ?: 0))
            ->latest()
            ->limit(self::POR_GRUPO)
            ->get(['id', 'numero', 'client_name', 'status', 'total']);

        return self::grupo('cotizaciones', 'Cotizaciones', $filas->map(fn ($c) => [
            'titulo'  => $c->etiqueta,
            'detalle' => $c->client_name.' · '.QuoteStatus::comercialPresentacion($c->status)['label'],
            'importe' => 'S/ '.LineMath::present(LineMath::canon((string) $c->total)),
            'url'     => route('bixosales.cotizaciones.show', $c->id),
        ])->all());
    }

    private static function pedidos(?User $user, Project $project, string $q, string $like): ?array
    {
        if (! $project->hasModule('orders') || ! self::puede($user, ['orders.ver', 'view-orders'])) {
            return null;
        }

        $filas = $project->orders()
            ->where(fn ($w) => $w->where('client_name', 'like', $like)
                ->orWhere('client_phone', 'like', $like)
                ->orWhere('numero', 'like', $like)
                ->orWhere('id', ltrim($q, '0') ?: 0))
            ->latest()
            ->limit(self::POR_GRUPO)
            ->get(['id', 'numero', 'client_name', 'status', 'total']);

        return self::grupo('pedidos', 'Pedidos', $filas->map(fn ($o) => [
            'titulo'  => $o->etiqueta,
            'detalle' => $o->client_name,
            'importe' => 'S/ '.LineMath::present(LineMath::canon((string) $o->total)),
            'url'     => route('bixosales.pedidos').'?q='.urlencode($o->client_name ?? ''),
        ])->all());
    }

    private static function productos(?User $user, Project $project, string $like): ?array
    {
        if (! $project->hasModule('catalog') || ! self::puede($user, ['catalog.ver', 'view-products', 'manage-products'])) {
            return null;
        }

        $filas = $project->products()
            ->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('sku', 'like', $like))
            ->orderBy('name')
            ->limit(self::POR_GRUPO)
            ->get(['id', 'name', 'sku', 'price', 'stock']);

        return self::grupo('productos', 'Productos', $filas->map(fn ($p) => [
            'titulo'  => $p->name,
            'detalle' => $p->sku ? 'SKU: '.$p->sku : '',
            'importe' => 'S/ '.LineMath::present(LineMath::canon((string) $p->price)),
            'url'     => route('catalog').'?q='.urlencode($p->name),
        ])->all());
    }

    private static function proveedores(?User $user, Project $project, string $like): ?array
    {
        if (! self::puede($user, ['proveedores.ver', 'manage-proveedores', 'compras.ver'])) {
            return null;
        }

        $filas = \App\Models\Proveedor::where('project_id', $project->id)
            ->where(fn ($w) => $w->where('name', 'like', $like)
                ->orWhere('contact_name', 'like', $like)
                ->orWhere('phone', 'like', $like))
            ->orderBy('name')
            ->limit(self::POR_GRUPO)
            ->get(['id', 'name', 'contact_name', 'phone']);

        return self::grupo('proveedores', 'Proveedores', $filas->map(fn ($p) => [
            'titulo'  => $p->name,
            'detalle' => trim(($p->contact_name ? $p->contact_name.' · ' : '').($p->phone ?? '')),
            'url'     => route('proveedores.index').'?q='.urlencode($p->name),
        ])->all());
    }
}
