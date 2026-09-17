<?php

namespace App\Http\Controllers;

use App\Models\DesignTemplate;
use App\Storefront\DesignTemplateService;
use Illuminate\Http\Request;

/**
 * "Mis plantillas": diseños guardados del constructor.
 * Todas las acciones operan sobre plantillas del dueño autenticado
 * (o superadmin). Aplicar SIEMPRE escribe en borrador.
 */
class DesignTemplateController extends Controller
{
    public function __construct(private readonly DesignTemplateService $service)
    {
    }

    private function owned(int $id): DesignTemplate
    {
        $template = DesignTemplate::findOrFail($id);
        abort_unless(auth()->user()->is_superadmin || $template->owner_id === auth()->id(), 403);
        return $template;
    }

    /** El usuario debe poder administrar el proyecto activo (mismo guard del builder). */
    private function activeProject(): \App\Models\Project
    {
        $project = app('active_project');
        abort_unless(\App\Storefront\BuilderAccess::allows($project, auth()->user()), 403);
        return $project;
    }

    public function index()
    {
        $templates = DesignTemplate::activeFor(auth()->id())->with('versions')->get();
        $archived = DesignTemplate::where('owner_id', auth()->id())->whereNotNull('archived_at')->get();

        return view('settings.design-templates', [
            'templates' => $templates,
            'archived' => $archived,
            'project' => $this->activeProject(),
        ]);
    }

    /** Guardar el diseño ACTUAL del proyecto activo como plantilla nueva. */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
            'category' => ['nullable', 'string', 'max:60'],
        ]);
        $project = $this->activeProject();
        $payload = $this->service->capture($project);

        $template = DesignTemplate::create($data + [
            'owner_id' => auth()->id(),
            'source_project_id' => $project->id,
            'thumbnail_path' => $project->setting('hero_image') ?: $project->setting('logo_url'),
        ]);
        $this->service->saveVersion($template, $payload, auth()->user(), 'Versión inicial');

        return back()->with('success', "Plantilla \"{$template->name}\" guardada (v1).");
    }

    /** Nueva versión desde el diseño actual del proyecto activo. */
    public function newVersion(Request $request, int $id)
    {
        $template = $this->owned($id);
        $notes = $request->validate(['notes' => ['nullable', 'string', 'max:300']])['notes'] ?? null;
        $version = $this->service->saveVersion($template, $this->service->capture($this->activeProject()), auth()->user(), $notes ?: 'Actualización desde el constructor');
        $template->touch();

        return back()->with('success', "Versión v{$version->version} creada.");
    }

    /** Aplicar (a BORRADOR) al proyecto activo, completa o por módulos. */
    public function apply(Request $request, int $id)
    {
        $template = $this->owned($id);
        $data = $request->validate([
            'parts' => ['required', 'array', 'min:1'],
            'parts.*' => ['string', 'max:40'],
            'version' => ['nullable', 'integer', 'min:1'],
        ]);
        $version = $data['version'] ?? null
            ? $template->versions()->where('version', $data['version'])->firstOrFail()
            : $template->latestVersion();
        abort_unless($version, 404);

        $payload = $version->decodedPayload();
        $payload['_template_id'] = $template->id;
        $payload['_template_version'] = $version->version;
        $result = $this->service->apply($this->activeProject(), $payload, $data['parts'], auth()->user());

        $msg = "Plantilla aplicada al borrador ({$result['settings']} ajustes, {$result['sections']} secciones). Revísala en el Constructor y publica o descarta.";
        if ($result['skipped_media']) $msg .= ' Archivos no encontrados omitidos: ' . count($result['skipped_media']) . '.';

        return back()->with('success', $msg);
    }

    public function update(Request $request, int $id)
    {
        $template = $this->owned($id);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
            'category' => ['nullable', 'string', 'max:60'],
            'thumbnail' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);
        if ($request->hasFile('thumbnail')) {
            $data['thumbnail_path'] = \App\Support\Imagen\ProcesadorImagenes::ruta(
                $request->file('thumbnail'), 'design-templates/thumbs', 'miniatura'
            );
        }
        unset($data['thumbnail']);
        $template->update($data);
        return back()->with('success', 'Plantilla actualizada.');
    }

    public function duplicate(int $id)
    {
        $template = $this->owned($id);
        $copy = $template->replicate(['is_default', 'is_favorite']);
        $copy->name = $template->name . ' (copia)';
        $copy->owner_id = auth()->id();
        $copy->save();
        $latest = $template->latestVersion();
        if ($latest) $this->service->saveVersion($copy, $latest->decodedPayload(), auth()->user(), 'Duplicada de ' . $template->name);

        return back()->with('success', "Duplicada como \"{$copy->name}\".");
    }

    /** Restaurar: crea una versión NUEVA con el payload antiguo (historial intacto). */
    public function restore(int $id, int $versionNumber)
    {
        $template = $this->owned($id);
        $old = $template->versions()->where('version', $versionNumber)->firstOrFail();
        $new = $this->service->saveVersion($template, $old->decodedPayload(), auth()->user(), "Restauración de v{$versionNumber}");

        return back()->with('success', "v{$versionNumber} restaurada como v{$new->version}.");
    }

    public function toggle(Request $request, int $id)
    {
        $template = $this->owned($id);
        $field = $request->validate(['field' => ['required', 'in:is_favorite,is_default,archived']])['field'];
        if ($field === 'archived') {
            $template->archived_at = $template->archived_at ? null : now();   // recuperable
        } elseif ($field === 'is_default') {
            DesignTemplate::where('owner_id', $template->owner_id)->update(['is_default' => false]);
            $template->is_default = true;
        } else {
            $template->is_favorite = !$template->is_favorite;
        }
        $template->save();
        return back();
    }

    public function export(int $id)
    {
        $template = $this->owned($id);
        $latest = $template->latestVersion();
        abort_unless($latest, 404);

        return response()->streamDownload(
            fn () => print($latest->payload),
            \Illuminate\Support\Str::slug($template->name) . "-v{$latest->version}.json",
            ['Content-Type' => 'application/json']
        );
    }

    public function import(Request $request)
    {
        $request->validate(['file' => ['required', 'file', 'mimetypes:application/json,text/plain', 'max:8192']]);
        $payload = json_decode((string) file_get_contents($request->file('file')->getRealPath()), true);
        if (!is_array($payload)) return back()->withErrors(['file' => 'El archivo no es un JSON válido.']);

        $check = $this->service->validateImport($payload);
        if ($check['errors']) return back()->withErrors(['file' => implode(' ', $check['errors'])]);

        $name = trim((string) $request->input('name')) ?: ('Importada ' . now()->format('d/m/Y H:i'));
        $template = DesignTemplate::create([
            'owner_id' => auth()->id(), 'name' => mb_substr($name, 0, 120),
            'description' => 'Plantilla importada.',
        ]);
        $this->service->saveVersion($template, $this->service->migrateSchema($payload), auth()->user(), 'Importación');

        $msg = "Plantilla \"{$template->name}\" importada.";
        if ($check['missing_media']) $msg .= ' Archivos multimedia no presentes: ' . count($check['missing_media']) . ' (se omitirán al aplicar).';

        return back()->with('success', $msg);
    }
}
