<?php

namespace App\Modules\Tienda\Storefront;

use App\Models\Project;

/**
 * Los números de contacto de la tienda.
 *
 * Antes solo cabían dos: un WhatsApp y un teléfono fijo. Un negocio con
 * varias líneas (ventas, soporte, un vendedor por zona) no tenía dónde
 * ponerlas. Ahora se guarda una lista en el ajuste `contact_numbers`, y el
 * WhatsApp principal sigue siendo el de siempre para no mover ninguna tienda
 * ya publicada: esta clase devuelve las dos cosas juntas y ordenadas.
 *
 * Formato guardado (JSON): [{"t":"whatsapp|telefono","n":"987654321","l":"Ventas"}]
 */
class ContactosTienda
{
    /** Cuántos números extra admite la tienda. Más que esto no se lee. */
    public const MAXIMO = 8;

    public const TIPOS = ['whatsapp' => 'WhatsApp', 'telefono' => 'Teléfono'];

    /**
     * Todos los contactos de la tienda: el principal primero, luego los extra.
     *
     * @return array<int,array{tipo:string,numero:string,etiqueta:string,enlace:string,visible:string}>
     */
    public static function todos(array $settings, Project $project): array
    {
        $out = [];

        // El WhatsApp principal, tal como lo resuelve la plantilla.
        $waPrincipal = self::soloDigitos(
            $settings['whatsapp'] ?? $settings['quote_whatsapp'] ?? $settings['contact_whatsapp']
            ?? $settings['store_whatsapp'] ?? $project->whatsapp ?? $project->phone ?? ''
        );
        if ($waPrincipal !== '') {
            $out[] = self::fila('whatsapp', $waPrincipal, trim((string) ($settings['whatsapp_label'] ?? '')));
        }

        // El fijo de toda la vida.
        $fijo = trim((string) ($settings['contact_phone'] ?? $project->phone ?? ''));
        if ($fijo !== '') {
            $out[] = self::fila('telefono', $fijo, trim((string) ($settings['contact_phone_label'] ?? '')));
        }

        foreach (self::extra($settings) as $c) {
            $out[] = self::fila($c['t'], $c['n'], $c['l']);
        }

        // Un mismo número escrito dos veces se muestra una sola.
        $vistos = [];

        return array_values(array_filter($out, function ($c) use (&$vistos) {
            $clave = $c['tipo'].':'.self::soloDigitos($c['numero']);
            if (isset($vistos[$clave])) {
                return false;
            }
            $vistos[$clave] = true;

            return true;
        }));
    }

    /** Solo los WhatsApp, para el botón flotante y los botones de consulta. */
    public static function whatsapps(array $settings, Project $project): array
    {
        return array_values(array_filter(
            self::todos($settings, $project),
            fn ($c) => $c['tipo'] === 'whatsapp'
        ));
    }

    /** Los números extra guardados en el Constructor, ya saneados. */
    public static function extra(array $settings): array
    {
        $crudo = $settings['contact_numbers'] ?? '';
        $lista = is_array($crudo) ? $crudo : json_decode((string) $crudo, true);
        if (! is_array($lista)) {
            return [];
        }

        $out = [];
        foreach ($lista as $fila) {
            if (! is_array($fila)) {
                continue;
            }
            $numero = trim((string) ($fila['n'] ?? ''));
            if (self::soloDigitos($numero) === '') {
                continue;
            }
            $tipo = ($fila['t'] ?? '') === 'telefono' ? 'telefono' : 'whatsapp';
            $out[] = [
                't' => $tipo,
                'n' => mb_substr($numero, 0, 30),
                'l' => mb_substr(trim((string) ($fila['l'] ?? '')), 0, 40),
            ];
            if (count($out) >= self::MAXIMO) {
                break;
            }
        }

        return $out;
    }

    /** Lo que se guarda cuando el Constructor manda la lista. */
    public static function normaliza($entrada): ?string
    {
        $limpias = self::extra(['contact_numbers' => $entrada]);

        return $limpias ? json_encode($limpias, JSON_UNESCAPED_UNICODE) : null;
    }

    /** Un WhatsApp peruano sin prefijo se manda con 51: si no, wa.me no abre. */
    public static function waInternacional(string $numero): string
    {
        $d = self::soloDigitos($numero);

        return $d !== '' && ! str_starts_with($d, '51') && strlen($d) === 9 ? '51'.$d : $d;
    }

    private static function fila(string $tipo, string $numero, string $etiqueta): array
    {
        $visible = trim($numero);

        return [
            'tipo'     => $tipo,
            'numero'   => $visible,
            'etiqueta' => $etiqueta,
            'visible'  => $visible,
            'enlace'   => $tipo === 'whatsapp'
                ? 'https://wa.me/'.self::waInternacional($numero)
                : 'tel:'.preg_replace('/[^\d+]/', '', $numero),
        ];
    }

    private static function soloDigitos($v): string
    {
        return preg_replace('/\D/', '', (string) $v) ?? '';
    }
}
