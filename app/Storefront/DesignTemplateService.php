<?php

namespace App\Storefront;

use App\Models\DesignTemplate;
use App\Models\DesignTemplateVersion;
use App\Models\Project;
use App\Models\User;
use App\Support\StorefrontSections;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Diseños guardados (Fase 3): captura y aplicación de plantillas como DATOS.
 *
 * Principios:
 *  - Schema MODULAR y versionado (schema_version=1). Los módulos aún no
 *    implementados (catalog, product_detail, cart, checkout_visual…) ya tienen
 *    lugar en el payload: agregar uno nuevo NO cambia capture()/apply().
 *  - La clasificación de settings es por PREFIJOS (extensible): una clave nueva
 *    cae automáticamente en su módulo o en `global`, sin tocar código.
 *  - NUNCA captura datos del negocio (EXCLUDED_PREFIXES/EXCLUDED_KEYS).
 *  - apply() SIEMPRE va a borrador (BuilderDraftService + draft de secciones):
 *    validar → borrador → vista previa → publicar o descartar.
 *  - Multimedia portable: el payload lista los archivos referenciados; al
 *    aplicar, una referencia cuyo archivo no exista se OMITE (se conserva lo
 *    del proyecto destino) y se reporta — nunca imágenes rotas.
 */
class DesignTemplateService
{
    public const SCHEMA_VERSION = 1;

    /** Módulos del schema (reservados aunque aún no capturen nada). */
    public const MODULES = [
        'global', 'identity', 'theme', 'typography', 'structure', 'header',
        'navigation', 'sections', 'catalog', 'product_cards', 'product_detail',
        'cart', 'checkout_visual', 'popups', 'floating_elements', 'footer',
        'animations', 'responsive', 'profiles', 'media',
    ];

    /** Prefijo de clave de setting → módulo. Primera coincidencia gana. */
    private const MODULE_PREFIXES = [
        'logo_' => 'identity', 'favicon' => 'identity', 'header_logo' => 'identity',
        'mobile_logo' => 'identity',
        'theme_preset' => 'theme', 'primary_color' => 'theme', 'secondary_color' => 'theme', 'accent_color' => 'theme',
        'page_bg' => 'theme', 'border_radius' => 'theme', 'btn_shape' => 'theme',
        'btn_show_icon' => 'theme', 'section_' => 'theme',
        'font' => 'typography',
        'header_' => 'header', 'announcement_' => 'header', 'menu_' => 'navigation',
        'hero_' => 'sections', 'promo_' => 'sections', 'trust_' => 'sections',
        'featured_' => 'sections', 'flash_sale_' => 'sections', 'intro_' => 'sections',
        'home_section_order' => 'sections',
        'catalog_' => 'catalog', 'txt_' => 'catalog', 'product_card_' => 'product_cards',
        'product_button_mode' => 'product_cards', 'btn_' => 'product_cards',
        'cart_' => 'cart', 'float_wa' => 'floating_elements', 'whatsapp_float' => 'floating_elements',
        'footer_' => 'footer',
        'anim_' => 'animations',
    ];

    /** Prefijos JAMÁS capturados (datos del negocio, privados o comerciales). */
    private const EXCLUDED_PREFIXES = [
        'payment_', 'contact_', 'shipping_', 'seo_', 'business_', 'wholesale_',
        'checkout_fields', 'quote_whatsapp', 'whatsapp_', 'store_whatsapp',
        'facebook_url', 'instagram_url', 'tiktok_url', 'youtube_url', 'linkedin_url',
        'require_address', 'store_mode', 'currency', 'accepted_payments',
    ];

    // ── CAPTURA ────────────────────────────────────────────────────────────

    public function capture(Project $project): array
    {
        $modules = array_fill_keys(self::MODULES, []);
        $media = [];

        // Settings visuales, clasificados por prefijo. Una clave DESCONOCIDA solo
        // entra a `global` si su forma es inequívocamente visual (allowlist);
        // si no, se OMITE y se registra para revisión — nunca se arriesga
        // capturar información comercial no contemplada.
        $unclassified = [];
        foreach ($project->settings()->pluck('value', 'key') as $key => $value) {
            if ($value === null || $value === '' || $this->isExcluded($key)) continue;
            $module = $this->moduleFor($key);
            if ($module === null) {
                if (!$this->looksVisual($key)) { $unclassified[] = $key; continue; }
                $module = 'global';
            }
            $modules[$module]['settings'][$key] = $value;
            $this->collectMedia($value, $media);
        }

        // Secciones de la portada (estructura + variante + contenido + orden).
        $modules['sections']['items'] = $project->storeSections()->where('page', 'home')
            ->orderBy('sort_order')->get()
            ->map(fn ($s) => [
                'component' => $s->component, 'variant' => $s->variant,
                'content' => is_array($s->content) ? $s->content : [],
                'is_enabled' => (bool) $s->is_enabled, 'sort_order' => (int) $s->sort_order,
                'show_desktop' => (bool) $s->show_desktop, 'show_tablet' => (bool) $s->show_tablet,
                'show_mobile' => (bool) $s->show_mobile,
            ])->values()->all();
        foreach ($modules['sections']['items'] as $item) $this->collectMedia($item['content'], $media);

        // Pop-ups (visuales completos; no llevan datos de negocio).
        $modules['popups']['items'] = $project->storePopups()->get()
            ->map(fn ($p) => collect($p->getAttributes())->except(['id', 'project_id', 'created_at', 'updated_at'])->all())
            ->values()->all();

        // Perfiles visuales (Niño/Niña…): solo identidad visual, no catálogo.
        $modules['profiles']['items'] = $project->catalogProfiles()->get()
            ->map(fn ($p) => collect($p->only(array_merge(
                ['name', 'slug', 'menu_label', 'description', 'is_enabled', 'is_default', 'show_in_menu', 'sort_order'],
                \App\Models\StoreCatalogProfile::VISUAL_KEYS
            )))->all())->values()->all();

        return [
            'schema_version' => self::SCHEMA_VERSION,
            'captured_at' => now()->toIso8601String(),
            'source' => ['project_id' => $project->id, 'name' => $project->name],
            'modules' => $modules,
            'media' => array_values(array_unique($media)),
            // Claves omitidas por no ser clasificables como visuales: quedan
            // visibles para el administrador (candidatas a mapear en MODULE_PREFIXES).
            'unclassified_keys' => array_values(array_unique($unclassified)),
        ];
    }

    // ── APLICACIÓN (siempre a borrador) ────────────────────────────────────

    /**
     * @param array $parts Módulos a aplicar (['all'] = todos los presentes).
     * @return array{settings:int, sections:int, skipped_media:array, warnings:array}
     */
    public function apply(Project $project, array $payload, array $parts, User $user): array
    {
        $payload = $this->migrateSchema($payload);
        $modules = (array) ($payload['modules'] ?? []);
        $all = in_array('all', $parts, true);
        $warnings = [];
        foreach (array_keys($modules) as $name) {
            if (!in_array($name, self::MODULES, true)) $warnings[] = "Módulo desconocido conservado: {$name}";
        }

        $drafts = app(BuilderDraftService::class);
        $writer = app(StoreSectionWriteService::class);
        $skippedMedia = [];
        $settingsCount = 0;
        $sectionsCount = 0;
        $createdPopups = [];

        return DB::transaction(function () use ($project, $payload, $modules, $all, $parts, $drafts, $writer, $user, &$skippedMedia, &$settingsCount, &$sectionsCount, &$createdPopups, $warnings) {
            // Registro de la aplicación: trazabilidad plantilla/versión/proyecto/usuario
            // y ancla para descartar con restauración exacta.
            $this->applicationId = DB::table('design_template_applications')->insertGetId([
                'project_id' => $project->id,
                'design_template_id' => $payload['_template_id'] ?? null,
                'template_version' => $payload['_template_version'] ?? null,
                'applied_by' => $user->id, 'status' => 'draft',
                'meta' => json_encode(['parts' => $parts]),
                'created_at' => now(), 'updated_at' => now(),
            ]);
            foreach ($modules as $name => $data) {
                if (!$all && !in_array($name, $parts, true)) continue;

                foreach ((array) ($data['settings'] ?? []) as $key => $value) {
                    if ($this->isExcluded($key)) continue;   // defensa extra en importaciones
                    if ($this->isMissingMedia($value)) { $skippedMedia[] = $key; continue; }
                    $drafts->putSetting($project, $key, (string) $value, $user->id);
                    $settingsCount++;
                }

                if ($name === 'popups') {
                    // StorePopup no tiene borrador: se crean DESACTIVADOS y ETIQUETADOS
                    // con la aplicación. Los popups PROPIOS del proyecto jamás se tocan.
                    // Dos aplicaciones consecutivas no se mezclan: la anterior en estado
                    // draft se limpia (solo SUS popups) antes de crear los nuevos.
                    $this->cleanupDraftApplications($project);
                    foreach ((array) ($data['items'] ?? []) as $item) {
                        $attrs = collect($item)->except(['id', 'project_id', 'created_at', 'updated_at', 'design_application_id'])->all();
                        if (isset($attrs['image_path']) && $this->isMissingMedia($attrs['image_path'])) {
                            $skippedMedia[] = (string) $attrs['image_path'];
                            $attrs['image_path'] = null;
                        }
                        $attrs['is_enabled'] = false;                       // llega apagado
                        $attrs['design_application_id'] = $this->applicationId;
                        $project->storePopups()->create($attrs);
                        $createdPopups[] = true;
                        $sectionsCount++;
                    }
                }

                if ($name === 'sections') {
                    StorefrontSections::ensure($project);
                    $valid = array_keys(StorefrontSections::COMPONENTS);
                    foreach ((array) ($data['items'] ?? []) as $item) {
                        if (!in_array($item['component'] ?? '', $valid, true)) {
                            $warnings[] = 'Sección desconocida omitida: ' . ($item['component'] ?? '?');
                            continue;   // compatibilidad futura: no rompe, avisa
                        }
                        $content = $this->stripMissingMedia((array) ($item['content'] ?? []), $skippedMedia);
                        $writer->saveDraft($project, 'home', $item['component'], [
                            'variant' => $item['variant'] ?? null,
                            'content' => $content,
                            'is_enabled' => (bool) ($item['is_enabled'] ?? false),
                            'sort_order' => (int) ($item['sort_order'] ?? 999),
                            'show_desktop' => (bool) ($item['show_desktop'] ?? true),
                            'show_tablet' => (bool) ($item['show_tablet'] ?? true),
                            'show_mobile' => (bool) ($item['show_mobile'] ?? true),
                        ]);
                        $sectionsCount++;
                    }
                }
            }

            // Auditoría del origen (referencia, no vínculo activo).
            Log::info('design_template', ['event' => 'applied', 'project' => $project->id,
                'template' => $payload['_template_id'] ?? null, 'version' => $payload['_template_version'] ?? null,
                'parts' => $parts, 'user' => $user->id]);

            return ['settings' => $settingsCount, 'sections' => $sectionsCount,
                    'application_id' => $this->applicationId,
                    'skipped_media' => array_values(array_unique($skippedMedia)), 'warnings' => $warnings];
        });
    }

    /** Id de la aplicación en curso (dentro de apply()). */
    private ?int $applicationId = null;

    /**
     * Descarta una aplicación: elimina SOLO los popups que ella creó (los del
     * proyecto quedan intactos) y restaura estados previos si los hubiera.
     */
    public function discardApplication(Project $project, int $applicationId): array
    {
        return DB::transaction(function () use ($project, $applicationId) {
            $app = DB::table('design_template_applications')
                ->where('id', $applicationId)->where('project_id', $project->id)->first();
            abort_unless($app, 404);

            $deleted = $project->storePopups()->where('design_application_id', $applicationId)->delete();

            // Restauración de elementos modificados (mecanismo listo; hoy la
            // aplicación solo CREA, nunca modifica popups existentes).
            $meta = json_decode((string) $app->meta, true) ?: [];
            foreach ((array) ($meta['previous_states'] ?? []) as $state) {
                if (!empty($state['id'])) {
                    $project->storePopups()->where('id', $state['id'])
                        ->update(collect($state)->except(['id'])->all());
                }
            }

            DB::table('design_template_applications')->where('id', $applicationId)
                ->update(['status' => 'discarded', 'updated_at' => now()]);

            return ['deleted_popups' => $deleted];
        });
    }

    /** Publica una aplicación: activa ÚNICAMENTE los popups que ella creó. */
    public function publishApplication(Project $project, int $applicationId): array
    {
        $enabled = $project->storePopups()->where('design_application_id', $applicationId)
            ->update(['is_enabled' => true]);
        DB::table('design_template_applications')->where('id', $applicationId)
            ->where('project_id', $project->id)->update(['status' => 'published', 'updated_at' => now()]);

        return ['enabled_popups' => $enabled];
    }

    /** Limpia popups de aplicaciones anteriores aún en borrador (no se mezclan). */
    private function cleanupDraftApplications(Project $project): void
    {
        $stale = DB::table('design_template_applications')
            ->where('project_id', $project->id)->where('status', 'draft')
            ->when($this->applicationId, fn ($q) => $q->where('id', '!=', $this->applicationId))
            ->pluck('id');
        if ($stale->isEmpty()) return;

        $project->storePopups()->whereIn('design_application_id', $stale)->delete();
        DB::table('design_template_applications')->whereIn('id', $stale)
            ->update(['status' => 'discarded', 'updated_at' => now()]);
    }

    // ── VERSIONES (inmutables) ─────────────────────────────────────────────

    public function saveVersion(DesignTemplate $template, array $payload, ?User $user, ?string $notes = null): DesignTemplateVersion
    {
        $next = (int) $template->versions()->max('version') + 1;
        return $template->versions()->create([
            'version' => $next,
            'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            'notes' => $notes, 'created_by' => $user?->id, 'created_at' => now(),
        ]);
    }

    /** Importación validada: estructura, schema, sin claves prohibidas. */
    public function validateImport(array $payload): array
    {
        $errors = [];
        if (!is_int($payload['schema_version'] ?? null)) $errors[] = 'schema_version ausente o inválido.';
        if (($payload['schema_version'] ?? 0) > self::SCHEMA_VERSION) $errors[] = 'Schema más nuevo que este sistema.';
        if (!is_array($payload['modules'] ?? null)) $errors[] = 'Estructura de módulos ausente.';
        foreach ((array) ($payload['modules'] ?? []) as $data) {
            foreach ((array) ($data['settings'] ?? []) as $key => $value) {
                if (!is_scalar($value) && $value !== null) { $errors[] = "Valor no escalar en {$key}."; break; }
                if ($this->isExcluded($key)) $errors[] = "Clave prohibida en importación: {$key}.";
                if (is_string($value) && preg_match('/<script|javascript:/i', $value)) $errors[] = "Contenido no permitido en {$key}.";
            }
        }
        $missing = array_values(array_filter((array) ($payload['media'] ?? []),
            fn ($p) => !Storage::disk('public')->exists($p)));

        return ['errors' => $errors, 'missing_media' => $missing];
    }

    /** Migración entre versiones de schema (hoy identidad; punto único futuro). */
    public function migrateSchema(array $payload): array
    {
        return $payload;   // v1 → v1
    }

    // ── Internos ───────────────────────────────────────────────────────────

    private function isExcluded(string $key): bool
    {
        foreach (self::EXCLUDED_PREFIXES as $prefix) {
            if (str_starts_with($key, $prefix)) return true;
        }
        return false;
    }

    /** null = clave desconocida (decide looksVisual si entra a global o se omite). */
    private function moduleFor(string $key): ?string
    {
        foreach (self::MODULE_PREFIXES as $prefix => $module) {
            if (str_starts_with($key, $prefix)) return $module;
        }
        return null;
    }

    /** Allowlist por FORMA: sufijos/segmentos inequívocamente visuales. */
    private function looksVisual(string $key): bool
    {
        return preg_match('/(_color|_style|_image|_img|_bg|_radius|_shadow|_font|_align|_height'
            . '|_width|_columns|_view|_shape|_icon|_spacing|_preset|_variant|_animation'
            . '|_overlay|_transition|_show_dots|_show_arrows|_autoplay|_duration)($|_)/', $key) === 1;
    }

    /** Registra rutas de archivos locales referenciadas en un valor/contenido. */
    private function collectMedia(mixed $value, array &$media): void
    {
        if (is_array($value)) { foreach ($value as $v) $this->collectMedia($v, $media); return; }
        if (is_string($value) && $this->looksLikeLocalPath($value)) $media[] = ltrim(preg_replace('#^storage/#', '', $value), '/');
    }

    private function looksLikeLocalPath(string $value): bool
    {
        return $value !== '' && !str_contains($value, '://') && !str_starts_with($value, 'data:')
            && preg_match('#^storage/|^logos/|^store-sections/|^uploads/#', $value) === 1;
    }

    private function isMissingMedia(mixed $value): bool
    {
        return is_string($value) && $this->looksLikeLocalPath($value)
            && !Storage::disk('public')->exists(ltrim(preg_replace('#^storage/#', '', $value), '/'));
    }

    /** Reemplaza referencias rotas dentro del contenido por null (conservador). */
    private function stripMissingMedia(array $content, array &$skipped): array
    {
        foreach ($content as $key => $value) {
            if (is_array($value)) $content[$key] = $this->stripMissingMedia($value, $skipped);
            elseif ($this->isMissingMedia($value)) { $content[$key] = null; $skipped[] = (string) $value; }
        }
        return $content;
    }
}
