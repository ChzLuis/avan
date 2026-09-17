<?php

namespace App\Support;

use App\Modules\Control\Models\AccessEvent;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Propiedad de un negocio — FASE 11 del plan de Perfiles y Accesos.
 *
 * El Dueño es quien manda dentro de SU negocio: `Gate::before` le concede todos
 * los permisos de ese proyecto y `CheckProjectMember` le da acceso aunque no
 * tenga ficha de empleado. Es distinto del Administrador de plataforma BIXO
 * (`users.is_superadmin`), que es interno de Eskala (§3.4 del plan).
 *
 * Hasta ahora `projects.owner_id` solo se escribía al crear el proyecto y no
 * había forma de cambiarlo: los 7 negocios de producción pertenecían a la cuenta
 * de superadmin, de modo que ningún cliente era dueño del suyo y la separación
 * del §3.4 no existía en la práctica.
 *
 * Las tres reglas que se protegen:
 *   1. Un proyecto SIEMPRE tiene un dueño. No se puede dejar sin él.
 *   2. El dueño tiene que ser miembro del proyecto (si no, no podría entrar).
 *   3. Todo cambio de dueño queda auditado.
 */
class ProjectOwnership
{
    /**
     * Traspasa la propiedad del negocio a otra persona.
     *
     * @throws ValidationException si el destinatario no puede ser dueño.
     */
    public static function transferir(Project $project, User $nuevoDueno): void
    {
        if ($project->owner_id === $nuevoDueno->id) {
            throw ValidationException::withMessages([
                'owner_id' => "{$nuevoDueno->name} ya es el dueño de este negocio.",
            ]);
        }

        DB::transaction(function () use ($project, $nuevoDueno) {
            $anterior = $project->owner;

            // Regla 2: el dueño tiene que poder entrar a su propio negocio.
            // Se le da la membresía si no la tenía, en vez de rechazar el cambio.
            ProjectMember::firstOrCreate(
                ['project_id' => $project->id, 'user_id' => $nuevoDueno->id],
                ['role' => 'owner']
            );

            $project->owner_id = $nuevoDueno->id;
            $project->save();

            AccessEvent::registrar('owner_transferred', $project->id, null, $nuevoDueno->id, [
                'from'      => $anterior?->name,
                'from_id'   => $anterior?->id,
                'to'        => $nuevoDueno->name,
                'proyecto'  => $project->name,
            ]);
        });
    }

    /**
     * ¿Se puede quitar a este usuario del proyecto sin dejarlo sin dueño?
     *
     * Regla 1. Se comprueba ANTES de borrar una membresía o una ficha de
     * empleado, no después: recuperar un proyecto huérfano exige tocar la base
     * de datos a mano.
     */
    public static function puedeSerRetirado(Project $project, int $userId): bool
    {
        return $project->owner_id !== $userId;
    }

    /** Igual que la anterior, pero corta la operación con un mensaje claro. */
    public static function exigirQueNoSeaElDueno(Project $project, int $userId, string $accion = 'quitar'): void
    {
        if (self::puedeSerRetirado($project, $userId)) {
            return;
        }

        $nombre = $project->owner?->name ?? 'esta persona';

        throw ValidationException::withMessages([
            'user_id' => "No puedes {$accion} a {$nombre}: es el dueño de «{$project->name}» y "
                       . 'un negocio no puede quedarse sin dueño. Traspasa primero la propiedad a otra persona.',
        ]);
    }

    /** Candidatos a dueño: los miembros del proyecto que tienen cuenta de acceso. */
    public static function candidatos(Project $project): \Illuminate\Support\Collection
    {
        return User::whereIn('id', $project->members()->pluck('user_id'))
            ->orWhereIn('id', $project->employees()->whereNotNull('user_id')->pluck('user_id'))
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'is_superadmin']);
    }
}
