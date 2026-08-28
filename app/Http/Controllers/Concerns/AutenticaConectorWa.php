<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Project;
use Illuminate\Http\Request;

/**
 * Autenticación y aislamiento por tenant del conector de WhatsApp (RISK-008/
 * RISK-009). El tenant se deriva de un secreto POR PROYECTO (`wa_bot_token`),
 * no de un id controlable por el cliente. El token global es puente de
 * compatibilidad (fuera del repo, en `config/services.wabot.token`) mientras
 * el conector migra.
 */
trait AutenticaConectorWa
{
    private function botTokenWa(): string
    {
        return (string) config('services.wabot.token');
    }

    /** Aborta 401 si el token no es un `wa_bot_token` de proyecto ni el global legacy. */
    protected function autenticarWa(Request $request): void
    {
        $token = (string) $request->input('token', $request->query('token', ''));
        $ok = $token !== '' && (
            hash_equals($this->botTokenWa(), $token) ||
            Project::where('wa_bot_token', $token)->exists()
        );
        abort_unless($ok, 401, 'Unauthorized');
    }

    /** El tenant derivado del secreto por proyecto, o null si vino el global legacy. */
    protected function tenantWa(Request $request): ?Project
    {
        $token = (string) $request->input('token', $request->query('token', ''));
        return $token !== '' ? Project::where('wa_bot_token', $token)->first() : null;
    }

    /**
     * Para operar sobre una entidad con `project_id`, el token debe ser el
     * `wa_bot_token` de su DUEÑO (o el global legacy). Cierra el cross-tenant:
     * el conector de la Empresa A no toca entidades de la Empresa B.
     */
    protected function autorizarDelTenant($model, Request $request): void
    {
        $token = (string) $request->input('token', $request->query('token', ''));
        $dueno = Project::find($model->project_id);
        abort_unless(
            $dueno && (
                hash_equals((string) $dueno->wa_bot_token, $token) ||
                hash_equals($this->botTokenWa(), $token)
            ),
            403
        );
    }
}
