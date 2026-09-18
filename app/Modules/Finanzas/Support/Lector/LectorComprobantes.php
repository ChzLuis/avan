<?php

namespace App\Modules\Finanzas\Support\Lector;

use App\Modules\Bots\Ia\Providers\AnthropicVision;
use App\Modules\Bots\Ia\Providers\GeminiVision;
use App\Modules\Bots\Ia\Providers\OpenAiVision;
use App\Modules\Bots\Ia\VisionProvider;
use App\Models\Project;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Lector de comprobantes: convierte una foto o un PDF en datos estructurados.
 *
 * La logica del proveedor queda AQUI y solo aqui. El controlador no sabe con
 * que modelo se lee, y cambiar de proveedor es cambiar un ajuste, no tocar
 * codigo repartido.
 *
 * Lo que este servicio NO hace, a proposito:
 *   - no busca clientes ni productos (eso es `EmparejadorCatalogo`);
 *   - no valida importes (eso es `ValidadorLectura`);
 *   - no crea comprobantes. Leer nunca emite: el usuario revisa primero.
 *
 * La clave es POR PROYECTO (`lector_api_key`), con la global del .env como
 * respaldo: un negocio grande puede poner la suya sin que los demas se
 * queden sin la funcion.
 */
class LectorComprobantes
{
    /** Lo que SUNAT admite y el modelo lee bien. */
    public const MIMES = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];

    /** 12 MB: una foto de celular ronda 3-5 MB; mas que esto es un escaneo sin comprimir. */
    public const MAX_BYTES = 12 * 1024 * 1024;

    public function __construct(private ?VisionProvider $proveedor = null) {}

    /** Si el negocio tiene la funcion encendida y con clave utilizable. */
    public static function disponible(?Project $project): bool
    {
        if (! $project) {
            return false;
        }
        if ((string) $project->setting('lector_comprobantes', '0') !== '1') {
            return false;
        }

        return filled(self::claveDe($project));
    }

    /**
     * Lee el archivo y devuelve la estructura normalizada.
     *
     * @return array{ok:bool, datos:array, mensaje:string, motivo:string}
     */
    public function leer(Project $project, UploadedFile $archivo): array
    {
        $mime = (string) $archivo->getMimeType();

        if (! in_array($mime, self::MIMES, true)) {
            return $this->fallo('Ese archivo no es una foto ni un PDF. Sube una imagen del comprobante o su PDF.', 'formato');
        }
        if ($archivo->getSize() > self::MAX_BYTES) {
            return $this->fallo('El archivo pesa demasiado. Haz la foto con menos resolución o comprime el PDF.', 'tamano');
        }

        $proveedor = $this->proveedor ?? self::proveedorDe($project);
        if (! $proveedor) {
            return $this->fallo('La lectura de comprobantes no está configurada para este negocio.', 'sin_configurar');
        }

        // Un PDF a un proveedor que solo admite imagen es un fallo entendible,
        // no un error tecnico: se dice que mande foto.
        if ($mime === 'application/pdf' && method_exists($proveedor, 'aceptaPdf') && ! $proveedor->aceptaPdf()) {
            return $this->fallo('Este negocio está configurado para leer solo fotos. Haz una foto del comprobante.', 'pdf_no_soportado');
        }

        try {
            $crudo = $proveedor->leerDocumento(
                base64_encode((string) file_get_contents($archivo->getRealPath())),
                $mime,
                $this->instruccion(),
                ['timeout' => 90, 'max_tokens' => 4096]
            );
        } catch (\Throwable $e) {
            Log::warning('Lector: el proveedor no respondió', [
                'project' => $project->id, 'proveedor' => $proveedor->nombre(), 'error' => $e->getMessage(),
            ]);

            return $this->fallo('No pudimos analizar el comprobante ahora mismo. Inténtalo de nuevo en un momento.', 'proveedor');
        }

        $datos = $this->aJson($crudo);
        if ($datos === null) {
            return $this->fallo('No pudimos leer los datos del comprobante. Prueba con otra foto más nítida.', 'ilegible');
        }

        if (($datos['legible'] ?? true) === false) {
            return $this->fallo(
                'La imagen no tiene suficiente claridad. Intenta tomar otra fotografía con mejor iluminación.',
                'borroso'
            );
        }

        return ['ok' => true, 'datos' => Normalizador::aplicar($datos), 'mensaje' => 'Comprobante leído', 'motivo' => 'ok'];
    }

    /** Proveedor de vision del negocio. Null si no hay clave. */
    public static function proveedorDe(Project $project): ?VisionProvider
    {
        $clave = self::claveDe($project);
        if (blank($clave)) {
            return null;
        }

        $motor  = (string) ($project->setting('lector_motor') ?: config('ia.lector.motor', 'anthropic'));
        $modelo = trim((string) $project->setting('lector_modelo'));

        return match ($motor) {
            'openai' => new OpenAiVision($clave, $modelo ?: (string) config('ia.providers.openai.model')),
            'gemini' => new GeminiVision($clave, $modelo ?: (string) config('ia.providers.gemini.model')),
            default  => new AnthropicVision($clave, $modelo ?: (string) config('ia.providers.anthropic.model')),
        };
    }

    /**
     * Clave del negocio; si no tiene, la global. Asi el interruptor sirve
     * tanto al que trae su cuenta como al que usa la de Eskala.
     */
    private static function claveDe(Project $project): ?string
    {
        $propia = trim((string) $project->setting('lector_api_key'));
        if ($propia !== '') {
            return $propia;
        }

        $motor = (string) ($project->setting('lector_motor') ?: config('ia.lector.motor', 'anthropic'));

        return config("ia.providers.{$motor}.key") ?: null;
    }

    /**
     * La instruccion. Se escribe en un solo sitio para que la forma del JSON
     * y lo que espera `Normalizador` no se separen nunca.
     */
    private function instruccion(): string
    {
        return <<<'TXT'
Eres un lector de comprobantes de pago peruanos (facturas y boletas de venta).
Lee el documento adjunto y devuelve UN SOLO objeto JSON, sin texto alrededor y sin ```.

REGLA MÁS IMPORTANTE: no confundas al EMISOR con el CLIENTE.
- El EMISOR es quien vende: aparece arriba, con el logo, junto al recuadro del RUC y la denominación del comprobante.
- El CLIENTE es quien compra: aparece bajo etiquetas como "Señor(es)", "Cliente", "Adquiriente", "Razón social", "Destinatario".
Si solo hay un RUC visible, es el del emisor y el cliente queda vacío.

Nunca inventes un dato. Si algo no se lee o no aparece, usa null. Es preferible un campo vacío a uno inventado.
Respeta las filas de la tabla de productos: cada fila del papel es un elemento del array, nunca los juntes.

Devuelve exactamente esta forma:

{
  "legible": true,
  "tipo": "factura" | "boleta" | null,
  "serie": "F001" | null,
  "numero": "00000123" | null,
  "fecha_emision": "YYYY-MM-DD" | null,
  "fecha_vencimiento": "YYYY-MM-DD" | null,
  "moneda": "PEN" | "USD" | null,
  "forma_pago": "CONTADO" | "CREDITO" | null,
  "observaciones": null,
  "precios_incluyen_igv": true | false | null,
  "emisor":  { "ruc": null, "razon_social": null, "nombre_comercial": null, "direccion": null },
  "cliente": { "tipo_doc": "RUC"|"DNI"|"CE"|null, "numero_doc": null, "razon_social": null, "direccion": null },
  "items": [
    { "codigo": null, "descripcion": "...", "cantidad": 1, "unidad": null,
      "precio_unitario": 0, "descuento": 0, "importe": 0 }
  ],
  "totales": {
    "gravadas": null, "exoneradas": null, "inafectas": null, "gratuitas": null,
    "descuento_global": null, "igv": null, "isc": null, "otros_cargos": null,
    "subtotal": null, "total": null, "total_en_letras": null
  },
  "confianza": { "tipo": 0.0, "serie": 0.0, "numero": 0.0, "fecha_emision": 0.0,
                 "cliente": 0.0, "items": 0.0, "total": 0.0 }
}

Detalles:
- "tipo": "factura" si dice FACTURA o el cliente tiene RUC; "boleta" si dice BOLETA.
- "precio_unitario" es el que figura en la columna de precio unitario del papel, tal cual, sin recalcular.
- "precios_incluyen_igv": true si el precio unitario del papel ya lleva IGV; false si el IGV va aparte. null si no puedes saberlo.
- "cantidad", "precio_unitario", "descuento" e "importe" son números, sin símbolo de moneda ni separador de miles.
- "descuento" en porcentaje (0 a 100). Si el papel da un importe descontado y no un porcentaje, deja 0.
- "confianza": tu seguridad de 0 a 1 en cada campo. Sé honesto: usa valores bajos si la imagen está borrosa o dudas.
- "legible": false solo si la imagen está tan mal que no se puede leer nada fiable.
TXT;
    }

    /** El modelo a veces envuelve el JSON en texto o en vallas: se rescata. */
    private function aJson(string $texto): ?array
    {
        $limpio = trim($texto);
        $limpio = preg_replace('/^```(?:json)?|```$/mi', '', $limpio) ?? $limpio;

        $ini = strpos($limpio, '{');
        $fin = strrpos($limpio, '}');
        if ($ini === false || $fin === false || $fin <= $ini) {
            return null;
        }

        $datos = json_decode(substr($limpio, $ini, $fin - $ini + 1), true);

        return is_array($datos) ? $datos : null;
    }

    private function fallo(string $mensaje, string $motivo): array
    {
        return ['ok' => false, 'datos' => [], 'mensaje' => $mensaje, 'motivo' => $motivo];
    }
}
