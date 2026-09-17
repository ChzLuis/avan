<?php

namespace App\Storefront;

use App\Models\Project;
use Illuminate\Support\Collection;

/**
 * Datos que comparten las variantes del pie de página del Constructor.
 *
 * Cada variante tiene su propia idea visual, pero todas beben de los mismos
 * ajustes: enlaces de "Información" y "Tienda", marcas con productos,
 * cuentas bancarias, razón social y RUC, y los iconos de redes y pagos.
 * Antes cada parcial lo resolvía a su manera (o no lo resolvía: los pies de
 * la plantilla actual ignoraban las columnas de enlaces que el Constructor
 * permite escribir). Un solo sitio, una sola regla.
 */
final class DatosPie
{
    /**
     * La paleta del pie según el "Diseño del pie" elegido en el Constructor.
     *
     * El diseño (oscuro clásico, claro elegante, color de marca, compacto)
     * solo afectaba a la composición clásica: cualquier otra traía sus propios
     * colores y el ajuste no hacía nada. Aquí se traduce a variables CSS que
     * TODAS las composiciones usan, y los colores que el negocio fija a mano
     * siguen mandando sobre el estilo.
     *
     * @return array<string,string> variables CSS listas para un atributo style
     */
    public static function paleta(string $estilo, string $fondo, string $texto, string $marca, bool $fondoElegido = false): array
    {
        $mezcla = static fn (string $c, int $pct, string $con) => "color-mix(in srgb, {$c} {$pct}%, {$con})";

        $p = match ($estilo) {
            // Claro elegante: papel, tinta oscura y líneas finas.
            'light' => [
                'bg' => '#f8fafc', 'fuerte' => '#eef2f7', 'text' => '#475569', 'title' => '#0f172a',
                'line' => '#e2e8f0', 'sup' => '#ffffff', 'sup-ink' => '#1f2937',
            ],
            // Color de marca: el pie ES la marca, con su propio degradado.
            'accent' => [
                'bg' => $marca, 'fuerte' => $mezcla($marca, 70, '#000'), 'text' => '#f1f5f9', 'title' => '#ffffff',
                'line' => 'rgba(255,255,255,.24)', 'sup' => $mezcla($marca, 86, '#000'), 'sup-ink' => '#f8fafc',
            ],
            // Oscuro clásico y compacto centrado comparten paleta; el segundo
            // solo cambia el aire y la alineación.
            default => [
                'bg' => $fondo, 'fuerte' => $mezcla($fondo, 74, '#000'), 'text' => $texto, 'title' => '#ffffff',
                'line' => 'rgba(255,255,255,.16)', 'sup' => $fondo, 'sup-ink' => $texto,
            ],
        };

        // Un fondo escrito a mano gana siempre, sea cual sea el diseño.
        if ($fondoElegido && $estilo !== 'light') {
            $p['bg'] = $fondo;
            $p['fuerte'] = $mezcla($fondo, 74, '#000');
            $p['sup'] = $fondo;
        }

        return $p;
    }

    /** Las variables CSS de la paleta, para el atributo style del pie. */
    public static function variables(array $p): string
    {
        $css = '';
        foreach ($p as $k => $v) {
            $css .= "--fp-{$k}:{$v};";
        }

        return $css;
    }

    /** Prefijo de las rutas legales: vacío en dominio propio, /{slug} en el subdominio. */
    public static function base(Project $project): string
    {
        return ($project->custom_domain && request()->getHost() === $project->custom_domain) ? '' : '/'.$project->slug;
    }

    /**
     * "Texto | /ruta" por línea → [['texto' => ..., 'url' => ...], ...].
     * Sin ruta, el texto se convierte en un enlace muerto y se descarta.
     * Las rutas relativas cuelgan de la tienda; las absolutas van tal cual.
     */
    public static function enlaces(?string $texto, string $base): array
    {
        $salida = [];
        foreach (preg_split('/\r\n|\r|\n/', (string) $texto) as $linea) {
            $partes = array_map('trim', explode('|', $linea, 2));
            $etiqueta = $partes[0] ?? '';
            $url      = $partes[1] ?? '';
            if ($etiqueta === '' || $url === '') {
                continue;
            }
            if (! preg_match('#^(https?:)?//#', $url) && ! str_starts_with($url, 'mailto:') && ! str_starts_with($url, 'tel:')) {
                $url = url($base.'/'.ltrim($url, '/'));
            }
            $salida[] = ['texto' => $etiqueta, 'url' => $url];
        }

        return $salida;
    }

    /** Las páginas legales fijas, con el nombre que la gente busca. */
    public static function legales(string $base): array
    {
        return [
            ['texto' => 'Política de privacidad', 'url' => url($base.'/privacidad')],
            ['texto' => 'Términos y condiciones',  'url' => url($base.'/terminos')],
            ['texto' => 'Libro de reclamaciones',  'url' => url($base.'/reclamaciones')],
        ];
    }

    /** Marcas con al menos un producto disponible, para la columna "Marcas". */
    public static function marcas(Project $project, int $max = 10): Collection
    {
        try {
            return \App\Models\CatalogValue::query()
                ->join('catalog_lists', 'catalog_lists.id', '=', 'catalog_values.catalog_list_id')
                ->where('catalog_lists.project_id', $project->id)
                ->where('catalog_lists.type', 'brand')
                ->where('catalog_values.is_active', true)
                ->whereExists(fn ($q) => $q->selectRaw('1')->from('products')
                    ->whereColumn('products.brand_catalog_id', 'catalog_values.id')
                    ->where('products.project_id', $project->id)
                    ->where('products.is_available', true))
                ->orderBy('catalog_values.sort_order')->orderBy('catalog_values.label')
                ->limit($max)
                ->pluck('catalog_values.label');
        } catch (\Throwable) {
            return collect();
        }
    }

    /** Cuentas bancarias tal como se escribieron en Configuración, una por línea. */
    public static function cuentas(array $settings): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) ($settings['cuentas_bancarias'] ?? '')))));
    }

    /** Razón social y RUC del comercio (obligatorios en la web en Perú). */
    public static function legal(array $settings, Project $project): array
    {
        return [
            // `razon_social` es lo que guarda el Constructor (Datos fiscales) y
            // lo que usa la facturacion; `legal_name` queda por compatibilidad
            // con las tiendas que ya lo tuvieran escrito.
            'nombre' => trim((string) ($settings['razon_social'] ?? ''))
                ?: trim((string) ($settings['legal_name'] ?? ''))
                ?: trim((string) $project->name),
            'ruc'    => preg_replace('/[^0-9]/', '', (string) ($settings['ruc'] ?? '')),
        ];
    }

    /** Horario en líneas: "Lun-Vie 9-18" y "Sáb 9-13" se leen mejor separadas. */
    public static function horario(?string $texto): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n|;|\|/', (string) $texto))));
    }

    /** Solo el trazo del icono de cada red, para pintarlo con el color del pie. */
    public static function iconoRed(string $red): string
    {
        return match ($red) {
            'Facebook'  => '<path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/>',
            'Instagram' => '<rect x="2" y="2" width="20" height="20" rx="5"/><circle cx="12" cy="12" r="4" fill="none" stroke="var(--fp-fondo,#fff)" stroke-width="2"/><circle cx="17.5" cy="6.5" r="1.3" fill="var(--fp-fondo,#fff)"/>',
            'TikTok'    => '<path d="M16 3a5 5 0 0 0 5 5v3a8 8 0 0 1-5-1.7V15a6 6 0 1 1-6-6v3a3 3 0 1 0 3 3V3z"/>',
            'YouTube'   => '<path d="M23 12s0-3.5-.45-5.2a2.75 2.75 0 0 0-1.94-1.94C18.9 4.4 12 4.4 12 4.4s-6.9 0-8.6.46A2.75 2.75 0 0 0 1.45 6.8C1 8.5 1 12 1 12s0 3.5.45 5.2a2.75 2.75 0 0 0 1.94 1.94c1.7.46 8.6.46 8.6.46s6.9 0 8.6-.46a2.75 2.75 0 0 0 1.94-1.94C23 15.5 23 12 23 12zM9.75 15.02V8.98L15.5 12l-5.75 3.02z"/>',
            'LinkedIn'  => '<path d="M4 3.5A1.5 1.5 0 1 1 4 6.5a1.5 1.5 0 0 1 0-3zM2.8 8h2.4v13H2.8zM9 8h2.3v1.8h.1c.3-.6 1.1-1.3 2.4-1.3 2.5 0 3 1.7 3 3.8V21h-2.4v-6.1c0-1.5 0-3.3-2-3.3s-2.3 1.6-2.3 3.2V21H9z"/>',
            default     => '<circle cx="12" cy="12" r="10"/>',
        };
    }

    /**
     * Ficha pequeña de cada medio de pago aceptado. Sin imágenes externas:
     * un rectángulo con el nombre, coloreado como la marca real.
     */
    public static function fichaPago(string $clave): ?string
    {
        $c = strtolower(trim($clave));
        $m = [
            'visa'          => ['Visa',       '#1a1f71', '#fff'],
            'mastercard'    => ['Mastercard', '#eb001b', '#fff'],
            'amex'          => ['Amex',       '#1e6cd6', '#fff'],
            'diners'        => ['Diners',     '#0079be', '#fff'],
            'yape'          => ['Yape',       '#742384', '#fff'],
            'plin'          => ['Plin',       '#00c3a5', '#0b1f1c'],
            'transferencia' => ['Transf.',    '#334155', '#fff'],
            'efectivo'      => ['Efectivo',   '#15803d', '#fff'],
            'bcp'           => ['BCP',        '#ff6600', '#fff'],
            'bbva'          => ['BBVA',       '#072146', '#fff'],
            'interbank'     => ['Interbank',  '#00a651', '#fff'],
            'scotiabank'    => ['Scotiabank', '#ec111a', '#fff'],
            'paypal'        => ['PayPal',     '#003087', '#fff'],
            'mercadopago'   => ['MercadoPago','#009ee3', '#fff'],
        ];
        if (! isset($m[$c])) {
            return null;
        }
        [$t, $bg, $fg] = $m[$c];

        return '<span class="fp-pago" style="background:'.$bg.';color:'.$fg.'" title="'.e($t).'">'.e($t).'</span>';
    }

    /** Lista de claves de pago aceptadas, con reserva sensata si nadie eligió. */
    public static function pagos(array $settings): array
    {
        $lista = json_decode((string) ($settings['accepted_payments'] ?? '[]'), true);
        $lista = is_array($lista) ? array_values(array_filter(array_map('strval', $lista))) : [];

        return $lista ?: ['visa', 'mastercard', 'yape', 'plin'];
    }
}
