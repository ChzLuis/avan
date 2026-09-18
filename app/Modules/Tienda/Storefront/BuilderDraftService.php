<?php

namespace App\Modules\Tienda\Storefront;


use App\Models\Project;
use Illuminate\Support\Facades\DB;

/**
 * Borrador y publicación del Constructor (B0).
 *
 * Estrategia (documentada en docs/builder-draft-publication-architecture.md):
 * - store_sections ya tiene columnas draft_* → se promueven por sección.
 * - project_settings NO tiene borrador → los cambios del Constructor se
 *   guardan en builder_drafts (resource_type=settings) y el público sigue
 *   leyendo project_settings hasta Publicar.
 * - Publicar = transacción: snapshot del estado público previo → aplicar
 *   drafts de settings → promover drafts de secciones → registrar versión
 *   en store_publications → limpiar borradores.
 */
class BuilderDraftService
{
    /** Borradores de settings como [key => value]. */
    /**
     * Ajuste del Constructor -> columna canonica de `projects` (revision 01).
     * publish() la usa para promover y rollback() para restaurar: si solo la
     * conociera publish(), deshacer dejaria el dato nuevo en la columna y el
     * viejo en el ajuste, que es justo la divergencia que esto quiere evitar.
     */
    private const COLUMNAS_MAESTRAS = [
        'business_name' => 'name',
        'contact_phone' => 'phone',
        'quote_whatsapp' => 'whatsapp',
        'contact_address' => 'address',
        'logo_url' => 'logo_url',
        'business_category' => 'category',
    ];

    public function settingsDrafts(Project $project): array
    {
        return DB::table('builder_drafts')
            ->where('project_id', $project->id)->where('resource_type', 'settings')
            ->pluck('payload', 'resource_key')
            ->map(fn ($payload) => json_decode($payload, true)['value'] ?? null)
            ->all();
    }

    /** Guarda un valor de setting en borrador (no toca project_settings). */
    public function putSetting(Project $project, string $key, mixed $value, ?int $userId = null): void
    {
        DB::table('builder_drafts')->updateOrInsert(
            ['project_id' => $project->id, 'resource_type' => 'settings', 'resource_key' => $key],
            ['payload' => json_encode(['value' => $value]), 'updated_by' => $userId,
                'updated_at' => now(), 'created_at' => now()]
        );
    }

    /** Descarta un borrador puntual (volver al valor publicado). */
    public function forgetSetting(Project $project, string $key): void
    {
        DB::table('builder_drafts')
            ->where('project_id', $project->id)->where('resource_type', 'settings')
            ->where('resource_key', $key)->delete();
    }

    /** Settings efectivos para PREVIEW: publicados + borradores encima. */
    public function effectiveSettings(Project $project): array
    {
        return array_merge(
            $project->settings()->pluck('value', 'key')->all(),
            array_filter($this->settingsDrafts($project), fn ($v) => $v !== null)
        );
    }

    public function hasDrafts(Project $project): bool
    {
        if (DB::table('builder_drafts')->where('project_id', $project->id)->exists()) return true;

        if ($project->storeSections()->where('page', 'home')->where('has_draft', true)->exists()) {
            return true;
        }

        return \App\Modules\Tienda\Models\StorePage::where('project_id', $project->id)->where('has_draft', true)->exists();
    }

    /**
     * Publicación transaccional. Devuelve el número de versión creado.
     * El checklist debe validarse ANTES (el controlador bloquea críticos).
     */
    public function publish(Project $project, ?int $userId = null, ?array $checklist = null): int
    {
        return DB::transaction(function () use ($project, $userId, $checklist) {
            // Bloqueo por proyecto contra publicaciones concurrentes.
            DB::table('projects')->where('id', $project->id)->lockForUpdate()->first();

            $drafts = $this->settingsDrafts($project);
            $draftKeys = array_keys($drafts);

            // 1) Snapshot del estado público previo (solo lo que cambia).
            $prevSettings = $draftKeys === [] ? [] : $project->settings()
                ->whereIn('key', $draftKeys)->pluck('value', 'key')->all();

            // Columnas maestras tal y como estan AHORA: sin esto el rollback no
            // puede devolverlas y quedan con el valor nuevo para siempre.
            $prevProject = [];
            foreach (self::COLUMNAS_MAESTRAS as $columna) {
                $prevProject[$columna] = $project->getOriginal($columna);
            }

            $sectionRows = $project->storeSections()->where('page', 'home')->lockForUpdate()->get();
            $prevSections = [];

            // 2) Promover settings.
            foreach ($drafts as $key => $value) {
                if ($value === null) continue;
                $project->settings()->updateOrCreate(['key' => $key], ['value' => $value]);
            }

            // 3) Promover secciones con borrador (COALESCE draft → publicado).
            foreach ($sectionRows as $section) {
                if (!$section->has_draft) continue;

                $prevSections[$section->component] = [
                    'is_enabled' => $section->is_enabled, 'sort_order' => $section->sort_order,
                    'content' => $section->content, 'show_desktop' => $section->show_desktop,
                    'show_mobile' => $section->show_mobile, 'show_tablet' => $section->show_tablet,
                ];

                $section->forceFill([
                    'is_enabled' => $section->draft_is_enabled ?? $section->is_enabled,
                    'sort_order' => $section->draft_sort_order ?? $section->sort_order,
                    'content' => $section->draft_content ?? $section->content,
                    'show_desktop' => $section->draft_show_desktop ?? $section->show_desktop,
                    'show_mobile' => $section->draft_show_mobile ?? $section->show_mobile,
                    'show_tablet' => $section->draft_show_tablet ?? $section->show_tablet,
                    'draft_is_enabled' => null, 'draft_sort_order' => null, 'draft_content' => null,
                    'draft_show_desktop' => null, 'draft_show_mobile' => null, 'draft_show_tablet' => null,
                    // CLAVE: sin esto, *ForPreview() lee drafts nulos y el preview
                    // oculta todas las secciones tras publicar.
                    'has_draft' => false,
                    'published_at' => now(),
                ])->save();
            }

            // 3b) Sincronizar las COLUMNAS canónicas de projects (revisión 01).
            // El resto del sistema (facturación, PDF, POS, portal, el bot de
            // WhatsApp) lee projects.name/phone/whatsapp/address/logo_url: si el
            // comerciante corrige el dato en el Constructor y solo se escribe el
            // setting, el bot sigue dictando la dirección vieja. Publicar es el
            // único punto de escritura; solo se tocan columnas cuyo setting
            // cambió en ESTA publicación.
            $cambiosProyecto = [];
            foreach (self::COLUMNAS_MAESTRAS as $settingKey => $columna) {
                if (! array_key_exists($settingKey, $drafts) || $drafts[$settingKey] === null) continue;
                $valor = trim((string) $drafts[$settingKey]);
                if ($settingKey === 'quote_whatsapp') {
                    $valor = preg_replace('/\D/', '', $valor);
                }
                if ($valor === '') continue; // vaciar el setting no borra el dato maestro
                $cambiosProyecto[$columna] = $valor;
            }
            if ($cambiosProyecto !== []) {
                $project->forceFill($cambiosProyecto)->save();
            }

            // 3c) Promover las PAGINAS con borrador. Hasta ahora se publicaban al
            // guardarse y la etapa 09 prometia "publicar tienda" cuando parte del
            // contenido ya habia salido sin pasar por ahi.
            $prevPages = [];
            foreach (\App\Modules\Tienda\Models\StorePage::where('project_id', $project->id)->where('has_draft', true)->get() as $pagina) {
                $prevPages[$pagina->key] = [
                    'title' => $pagina->title,
                    'content' => $pagina->content,
                    'is_enabled' => $pagina->is_enabled,
                ];
                $pagina->publicarBorrador();
            }

            // 4) Registrar versión publicada.
            $version = (int) DB::table('store_publications')->where('project_id', $project->id)->max('version') + 1;
            DB::table('store_publications')->insert([
                'project_id' => $project->id, 'version' => $version,
                'snapshot' => json_encode(['settings' => $prevSettings, 'sections' => $prevSections, 'project' => $prevProject, 'pages' => $prevPages]),
                'checklist' => $checklist ? json_encode($checklist) : null,
                'published_by' => $userId, 'created_at' => now(), 'updated_at' => now(),
            ]);

            // 5) Limpiar borradores de settings ya promovidos.
            DB::table('builder_drafts')
                ->where('project_id', $project->id)->where('resource_type', 'settings')->delete();

            return $version;
        });
    }

    /** Restaura el estado público previo a una versión publicada. */
    public function rollback(Project $project, int $version): bool
    {
        $row = DB::table('store_publications')
            ->where('project_id', $project->id)->where('version', $version)->first();
        if (!$row) return false;

        $snapshot = json_decode($row->snapshot, true) ?: [];

        DB::transaction(function () use ($project, $snapshot) {
            // Las columnas maestras se restauran TAL CUAL estaban, NULL incluido:
            // no se reconstruyen desde los alias, que pueden haber divergido.
            if (array_key_exists('project', $snapshot) && is_array($snapshot['project'])) {
                $columnas = array_intersect_key(
                    $snapshot['project'],
                    array_flip(array_values(self::COLUMNAS_MAESTRAS))
                );
                if ($columnas !== []) {
                    $project->forceFill($columnas)->save();
                }
            }

            foreach (($snapshot['settings'] ?? []) as $key => $value) {
                $project->settings()->updateOrCreate(['key' => $key], ['value' => $value]);
            }
            foreach (($snapshot['pages'] ?? []) as $key => $state) {
                \App\Modules\Tienda\Models\StorePage::where('project_id', $project->id)->where('key', $key)
                    ->update([
                        'title' => $state['title'],
                        'content' => is_array($state['content']) ? json_encode($state['content']) : $state['content'],
                        'is_enabled' => $state['is_enabled'],
                    ]);
            }

            foreach (($snapshot['sections'] ?? []) as $component => $state) {
                $project->storeSections()->where('page', 'home')
                    ->where('component', $component)->update([
                        'is_enabled' => $state['is_enabled'], 'sort_order' => $state['sort_order'],
                        'content' => is_array($state['content']) ? json_encode($state['content']) : $state['content'],
                        'show_desktop' => $state['show_desktop'], 'show_mobile' => $state['show_mobile'],
                        'show_tablet' => $state['show_tablet'],
                    ]);
            }
        });

        return true;
    }
}
