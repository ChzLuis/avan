<?php

namespace App\Support\WhatsappCloud;

use App\Models\WaCanal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Comprueba que un webhook entrante viene de verdad de Meta.
 *
 * La URL del webhook es PUBLICA por diseño: Meta la llama sin token ni sesion.
 * Lo unico que distingue una llamada legitima de la de un tercero es la firma
 * HMAC-SHA256 del cuerpo con el App Secret de la app de Meta. Sin esa
 * comprobacion, cualquiera que conozca la URL puede inventar un
 * `phone_number_id` real y hacer que el bot responda, cotice o registre leads
 * en nombre de un cliente.
 *
 * Se comparte entre los webhooks para que la regla viva en UN solo sitio.
 */
trait VerificaFirmaMeta
{
    /**
     * ¿La firma del cuerpo corresponde al App Secret de este canal?
     *
     * Devuelve true cuando el canal AUN no tiene `app_secret` configurado: es
     * una concesion deliberada para no tumbar las lineas que ya estaban
     * funcionando antes de que existiera esta comprobacion. Esas llamadas se
     * registran como `sin_firmar` para poder detectarlas y cerrarlas; cuando
     * un canal tiene secreto, la firma es obligatoria y se falla cerrado.
     */
    protected function firmaMetaValida(Request $request, ?WaCanal $canal): bool
    {
        $secreto = (string) ($canal?->app_secret ?? '');

        if ($secreto === '') {
            Log::warning('wa_webhook.sin_firmar', [
                'canal'    => $canal?->id,
                'proyecto' => $canal?->project_id,
                'ip'       => $request->ip(),
                'aviso'    => 'Canal sin app_secret: el webhook acepta sin verificar.',
            ]);

            return true;
        }

        $cabecera = (string) $request->header('X-Hub-Signature-256', '');
        if (! str_starts_with($cabecera, 'sha256=')) {
            $this->registrarFirmaInvalida($request, $canal, 'sin cabecera de firma');

            return false;
        }

        $esperada = 'sha256=' . hash_hmac('sha256', $request->getContent(), $secreto);

        if (! hash_equals($esperada, $cabecera)) {
            $this->registrarFirmaInvalida($request, $canal, 'firma no coincide');

            return false;
        }

        return true;
    }

    private function registrarFirmaInvalida(Request $request, ?WaCanal $canal, string $motivo): void
    {
        Log::warning('wa_webhook.firma_invalida', [
            'canal'    => $canal?->id,
            'proyecto' => $canal?->project_id,
            'ip'       => $request->ip(),
            'motivo'   => $motivo,
        ]);
    }
}
