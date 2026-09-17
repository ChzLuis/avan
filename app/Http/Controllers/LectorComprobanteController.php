<?php

namespace App\Http\Controllers;

use App\Models\LecturaComprobante;
use App\Models\Project;
use App\Support\Lector\EmparejadorCatalogo;
use App\Support\Lector\LectorComprobantes;
use App\Support\Lector\ValidadorLectura;
use Illuminate\Http\Request;

/**
 * Lector de comprobantes: de una foto o un PDF a los datos del formulario.
 *
 * Este controlador NO emite nada. Su única salida es una estructura que el
 * formulario de siempre entiende; a partir de ahí manda el flujo existente,
 * con sus validaciones y su envío a SUNAT. Una lectura equivocada no puede
 * declararse sola.
 */
class LectorComprobanteController extends Controller
{
    /**
     * Analiza el archivo y devuelve lo leído, ya cruzado con el catálogo.
     */
    public function analizar(Request $request, LectorComprobantes $lector)
    {
        /** @var Project $project */
        $project = app('active_project');

        if (! LectorComprobantes::disponible($project)) {
            return response()->json([
                'ok'      => false,
                'mensaje' => 'La lectura de comprobantes no está activada para este negocio.',
            ], 403);
        }

        // Se valida el MIME REAL, no la extensión: un .jpg puede ser cualquier cosa.
        $request->validate([
            'archivo' => [
                'required', 'file',
                'mimetypes:'.implode(',', LectorComprobantes::MIMES),
                'max:'.(int) (LectorComprobantes::MAX_BYTES / 1024),
            ],
        ], [
            'archivo.mimetypes' => 'Sube una foto (JPG, PNG o WEBP) o el PDF del comprobante.',
            'archivo.max'       => 'El archivo pesa demasiado. Hazlo con menos resolución.',
        ]);

        $archivo = $request->file('archivo');

        $lectura = LecturaComprobante::create([
            'project_id' => $project->id,
            'user_id'    => $request->user()?->id,
            'estado'     => 'procesando',
            'archivo'    => $archivo->getClientOriginalName(),
            'mime'       => $archivo->getMimeType(),
            'bytes'      => $archivo->getSize(),
            'motor'      => LectorComprobantes::proveedorDe($project)?->nombre(),
        ]);

        $resultado = $lector->leer($project, $archivo);

        if (! $resultado['ok']) {
            $lectura->update(['estado' => 'error', 'mensaje_error' => $resultado['mensaje']]);

            return response()->json([
                'ok'      => false,
                'mensaje' => $resultado['mensaje'],
                'motivo'  => $resultado['motivo'],
            ], 422);
        }

        $datos = $resultado['datos'];

        // Se cruza con lo que el negocio ya tiene: productos del catálogo y
        // clientes de comprobantes anteriores.
        $emparejador       = new EmparejadorCatalogo($project);
        $datos['items']    = $emparejador->items($datos['items']);
        $datos['cliente']  = $emparejador->cliente($datos['cliente']);

        $revision = (new ValidadorLectura($project))->revisar($datos);

        $lectura->update([
            'estado'     => $revision['avisos'] ? 'requiere_revision' : 'procesado',
            'resultado'  => $datos,
            'avisos'     => $revision['avisos'],
            'doc_tipo'   => $datos['tipo'],
            'doc_serie'  => $datos['serie'],
            'doc_numero' => $datos['numero'],
        ]);

        return response()->json([
            'ok'        => true,
            'lectura'   => $lectura->id,
            'datos'     => $datos,
            'avisos'    => $revision['avisos'],
            'revisar'   => $revision['revisar'],
            'duplicado' => $revision['duplicado'],
        ]);
    }

    /**
     * Convierte lo leído en el `form` que espera el formulario de emisión.
     *
     * Vive en el servidor y no en Alpine a propósito: si mañana cambian los
     * campos del comprobante, se tocan aquí y no en tres pantallas.
     */
    public function aplicar(Request $request)
    {
        /** @var Project $project */
        $project = app('active_project');

        $lectura = LecturaComprobante::where('project_id', $project->id)
            ->findOrFail((int) $request->input('lectura'));

        $datos = (array) $lectura->resultado;
        if (! $datos) {
            return response()->json(['ok' => false, 'mensaje' => 'Esa lectura ya no está disponible.'], 404);
        }

        // El usuario pudo cambiar el producto elegido en la pantalla de revisión.
        $elegidos = (array) $request->input('productos', []);

        $items = [];
        foreach ($datos['items'] as $i => $item) {
            $productId = $elegidos[$i]['product_id'] ?? $item['product_id'] ?? null;
            $nombre    = $elegidos[$i]['descripcion'] ?? $item['descripcion'];

            $items[] = [
                'description' => (string) $nombre,
                'unit'        => $item['unidad'] ?: 'NIU',
                'quantity'    => (float) $item['cantidad'],
                // El precio que manda es el del papel: es el documento que se
                // está importando, no una venta nueva a precio de hoy.
                'unit_price'  => (float) $item['precio_unitario'],
                'discount'    => (float) ($item['descuento'] ?? 0),
                'catalogKey'  => $productId ? 'product-'.$productId : null,
                'showSuggestions' => false,
                'suggestions'     => [],
                'activeSuggestion' => -1,
            ];
        }

        $tipo = in_array($datos['tipo'], ['factura', 'boleta'], true) ? $datos['tipo'] : 'boleta';

        $form = [
            'type'              => $tipo,
            'serie'             => $tipo === 'factura'
                ? ($project->setting('serie_factura') ?: 'F001')
                : ($project->setting('serie_boleta') ?: 'B001'),
            'correlativo'       => '',
            // La fecha del papel puede ser vieja y SUNAT solo admite 3 días
            // atrás: si no cabe, se deja hoy y el usuario decide.
            'issue_date'        => $this->fechaAdmisible($datos['fecha_emision']),
            'due_date'          => $datos['fecha_vencimiento'] ?? '',
            'client_name'       => $datos['cliente']['razon_social'] ?? '',
            'client_phone'      => $datos['cliente']['telefono'] ?? '',
            'client_email'      => $datos['cliente']['email'] ?? '',
            'client_doc_type'   => $datos['cliente']['tipo_doc'] ?? '',
            'client_doc_number' => $datos['cliente']['numero_doc'] ?? '',
            'client_address'    => $datos['cliente']['direccion'] ?? '',
            'payment_method'    => $datos['forma_pago'] ?? '',
            'currency'          => $datos['moneda'] ?? 'PEN',
            'notes'             => $datos['observaciones'] ?? '',
            // Si el papel no lo dice, se respeta cómo trabaja el negocio.
            'igv_included'      => $datos['igv_incluido'] ?? true,
            'items'             => $items,
        ];

        return response()->json(['ok' => true, 'form' => $form, 'lectura' => $lectura->id]);
    }

    /** SUNAT no acepta comprobantes con más de 3 días de antigüedad. */
    private function fechaAdmisible(?string $fecha): string
    {
        $hoy = now()->format('Y-m-d');
        if (! $fecha) {
            return $hoy;
        }

        $limite = now()->subDays(3)->format('Y-m-d');

        return ($fecha >= $limite && $fecha <= $hoy) ? $fecha : $hoy;
    }
}
