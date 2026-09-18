<?php

namespace App\Modules\Finanzas\Support\Lector;

use App\Modules\Finanzas\Models\Invoice;
use App\Models\Product;
use App\Models\Project;
use App\Support\ProjectContext;

/**
 * Ata lo leido en el papel con lo que ya existe en el sistema.
 *
 * Sin esto el lector solo copiaria texto: el comprobante saldria con una
 * descripcion suelta y sin `product_id`, y perderia el enlace con inventario,
 * SKU y reportes. Por eso cada linea se intenta casar con el catalogo.
 *
 * La busqueda difusa NO se reimplementa: se reutiliza
 * `ProjectContext::buscarTolerante`, que ya normaliza tildes, plurales y
 * erratas y esta probada en el bot y en el POS.
 */
class EmparejadorCatalogo
{
    /** Por encima de esto la sugerencia se aplica sola. */
    private const AUTO = 0.90;

    /** Por debajo de esto no se sugiere nada: mejor vacio que un producto equivocado. */
    private const MINIMO = 0.45;

    public function __construct(private Project $project) {}

    /**
     * Devuelve las lineas con su producto sugerido y las alternativas.
     * Nunca cambia el precio leido: eso lo decide el usuario.
     */
    public function items(array $items): array
    {
        $ctx = ProjectContext::for($this->project);

        return array_map(function (array $item) use ($ctx) {
            $sugerencias = $this->sugerir($ctx, $item);
            $mejor       = $sugerencias[0] ?? null;

            $item['sugerencias'] = $sugerencias;
            $item['product_id']  = null;
            $item['product_nombre'] = null;
            $item['match_confianza'] = $mejor['confianza'] ?? 0.0;
            // La descripcion del papel se guarda siempre: es la trazabilidad
            // de por que esta linea acabo apuntando a este producto.
            $item['descripcion_origen'] = $item['descripcion'];

            if ($mejor && $mejor['confianza'] >= self::AUTO) {
                $item['product_id']     = $mejor['id'];
                $item['product_nombre'] = $mejor['nombre'];
                $item['descripcion']    = $mejor['nombre'];
                $item['precio_catalogo'] = $mejor['precio'];
            }

            return $item;
        }, $items);
    }

    /**
     * Sugerencias ordenadas. Prioridad: codigo/SKU exacto, nombre exacto y
     * despues la busqueda tolerante.
     *
     * @return array<int, array{id:int, nombre:string, sku:?string, precio:float, unidad:?string, confianza:float}>
     */
    private function sugerir(ProjectContext $ctx, array $item): array
    {
        // 1) Codigo o SKU exacto: es la unica coincidencia que no admite duda.
        $codigo = trim((string) ($item['codigo'] ?? ''));
        if ($codigo !== '') {
            $porSku = Product::where('project_id', $this->project->id)
                ->where('sku', $codigo)->first();
            if ($porSku) {
                return [$this->fila($porSku, 1.0)];
            }
        }

        $descripcion = trim((string) ($item['descripcion'] ?? ''));
        if ($descripcion === '') {
            return [];
        }

        // 2) Nombre idéntico salvo mayúsculas y espacios.
        $exacto = Product::where('project_id', $this->project->id)
            ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($descripcion)])
            ->first();
        if ($exacto) {
            return [$this->fila($exacto, 0.99)];
        }

        // 3) Búsqueda tolerante. Su puntuación es abierta, así que se lleva a
        //    una escala 0-1 relativa al mejor resultado: lo que importa es
        //    cuánto destaca el primero, no su valor absoluto.
        $encontrados = $ctx->buscarTolerante($descripcion, 5)
            ->filter(fn ($p) => ($p['tipo'] ?? 'producto') === 'producto');

        if ($encontrados->isEmpty()) {
            return [];
        }

        $palabras = max(1, count(array_filter(explode(' ', $descripcion), fn ($w) => mb_strlen($w) >= 2)));
        $filas = [];
        foreach ($encontrados->values() as $i => $p) {
            // La primera vale más que la segunda: si el catálogo devuelve varias
            // parecidas, ninguna merece aplicarse sola.
            $confianza = max(0.0, 0.88 - ($i * 0.14));

            // Una descripción larga que solo casa por una palabra es sospechosa.
            $comunes = $this->palabrasComunes($descripcion, (string) $p['nombre']);
            $confianza *= min(1.0, 0.55 + ($comunes / $palabras) * 0.65);

            if ($confianza < self::MINIMO) {
                continue;
            }

            $filas[] = [
                'id'        => (int) $p['id'],
                'nombre'    => (string) $p['nombre'],
                'sku'       => $p['sku'] ?? null,
                'precio'    => (float) ($p['precio'] ?? 0),
                'unidad'    => null,
                'confianza' => round($confianza, 2),
            ];
        }

        return $filas;
    }

    private function palabrasComunes(string $a, string $b): int
    {
        $norm = static function (string $t): array {
            $t = mb_strtolower($t);
            $t = strtr($t, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n']);
            $t = preg_replace('/[^a-z0-9 ]/', ' ', $t) ?? '';

            return array_filter(explode(' ', $t), fn ($w) => mb_strlen($w) >= 2);
        };

        return count(array_intersect($norm($a), $norm($b)));
    }

    private function fila(Product $p, float $confianza): array
    {
        return [
            'id'        => $p->id,
            'nombre'    => $p->name,
            'sku'       => $p->sku,
            'precio'    => (float) $p->price,
            'unidad'    => $p->unit,
            'confianza' => $confianza,
        ];
    }

    /**
     * Datos del cliente ya conocidos. La tabla `clients` no guarda RUC ni DNI
     * —el documento fiscal solo vive en los comprobantes—, asi que se busca
     * en el historial de comprobantes, igual que hace el portal de emision.
     */
    public function cliente(array $cliente): array
    {
        $doc = trim((string) ($cliente['numero_doc'] ?? ''));
        if ($doc === '') {
            return $cliente + ['conocido' => false];
        }

        $previo = Invoice::where('project_id', $this->project->id)
            ->where('client_doc_number', $doc)
            ->latest('id')
            ->first(['client_name', 'client_doc_type', 'client_doc_number', 'client_address', 'client_phone', 'client_email']);

        if (! $previo) {
            return $cliente + ['conocido' => false];
        }

        // Lo ya registrado manda sobre lo leido: viene de SUNAT o lo tecleo
        // el negocio, y una foto borrosa no debe pisar un dato bueno.
        return [
            'tipo_doc'     => $previo->client_doc_type ?: ($cliente['tipo_doc'] ?? null),
            'numero_doc'   => $previo->client_doc_number,
            'razon_social' => $previo->client_name ?: ($cliente['razon_social'] ?? null),
            'direccion'    => $previo->client_address ?: ($cliente['direccion'] ?? null),
            'telefono'     => $previo->client_phone,
            'email'        => $previo->client_email,
            'conocido'     => true,
        ];
    }
}
